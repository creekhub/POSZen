import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import JsBarcode from 'jsbarcode';
import $ from 'jquery';
import Quill from 'quill';
import select2 from 'select2';
import 'quill/dist/quill.snow.css';
import 'select2/dist/css/select2.min.css';

window.Quill = Quill;
window.$ = window.jQuery = $;
select2(window, $);

const printerCommands = {
	init: Uint8Array.from([0x1b, 0x40]),
	alignLeft: Uint8Array.from([0x1b, 0x61, 0x00]),
	alignCenter: Uint8Array.from([0x1b, 0x61, 0x01]),
	boldOn: Uint8Array.from([0x1b, 0x45, 0x01]),
	boldOff: Uint8Array.from([0x1b, 0x45, 0x00]),
	cut: Uint8Array.from([0x1d, 0x56, 0x00]),
	drawerPulse: Uint8Array.from([0x1b, 0x70, 0x00, 0x19, 0xfa]),
};

class PosHardware {
	port = null;
	writer = null;
 usbDevice = null;
 usbEndpointNumber = null;
	lastReceipt = null;

	async connectPrinter() {
		if (!('serial' in navigator)) {
			this.setStatus('Web Serial is unavailable in this browser.');
			return false;
		}

		try {
			if (this.writer) {
				this.writer.releaseLock();
				this.writer = null;
			}

			const ports = await navigator.serial.getPorts();
			this.port = ports[0] || await navigator.serial.requestPort();
			await this.port.open({ baudRate: 9600 });
			this.writer = this.port.writable.getWriter();
			this.setStatus('Printer connected.');
			return true;
		} catch (error) {
			const message = error.name === 'NotFoundError'
				? 'Printer selection cancelled.'
				: error.name === 'InvalidStateError'
					? 'The serial printer is already open. Close other printer software and retry.'
					: `Printer error: ${error.message}`;
			this.setStatus(message);
			return false;
		}
	}

	async connectUsb() {
		if (!window.isSecureContext) {
			this.setStatus('USB requires HTTPS or localhost. Open the POS from http://localhost:8000.');
			return false;
		}

		if (!('usb' in navigator)) {
		 this.setStatus('WebUSB is unavailable in this browser.');
		 return false;
		}

		try {
		 const devices = await navigator.usb.getDevices();
		 this.usbDevice = devices[0] || await navigator.usb.requestDevice({ filters: [] });
		 await this.usbDevice.open();

		 if (!this.usbDevice.configuration) {
			await this.usbDevice.selectConfiguration(1);
		 }

		 for (const usbInterface of this.usbDevice.configuration.interfaces) {
			for (const alternate of usbInterface.alternates) {
				const outputEndpoint = alternate.endpoints.find((endpoint) => endpoint.direction === 'out');

				if (!outputEndpoint) {
					continue;
				}

				if (usbInterface.alternate?.alternateSetting !== alternate.alternateSetting) {
					await this.usbDevice.selectAlternateInterface(usbInterface.interfaceNumber, alternate.alternateSetting);
				}

				await this.usbDevice.claimInterface(usbInterface.interfaceNumber);
				this.usbEndpointNumber = outputEndpoint.endpointNumber;
				this.setStatus(`USB device connected: ${this.usbDevice.productName || 'POS hardware'}.`);
				return true;
			}
		 }

		 throw new Error('The USB device has no writable output endpoint.');
		} catch (error) {
			this.usbDevice = null;
			this.usbEndpointNumber = null;
			const message = error.name === 'NotFoundError'
				? 'USB device selection cancelled.'
				: error.name === 'SecurityError' || error.name === 'NotAllowedError'
					? 'USB access denied. This printer is using the Windows printer driver. Use Connect Serial for its COM port, or install WinUSB with Zadig to use WebUSB.'
					: `USB error: ${error.message}`;
			this.setStatus(message);
			return false;
		}
	 }

	async write(...chunks) {
		if (!this.writer && !this.usbDevice) {
		 this.setStatus('Connect the USB or serial receipt printer first.');
			return false;
		}

		const totalLength = chunks.reduce((length, chunk) => length + chunk.length, 0);
		const data = new Uint8Array(totalLength);
		let offset = 0;

		for (const chunk of chunks) {
			data.set(chunk, offset);
			offset += chunk.length;
		}

		try {
		 if (this.writer) {
			await this.writer.write(data);
		 } else {
			await this.usbDevice.transferOut(this.usbEndpointNumber, data);
		 }

		 return true;
		} catch (error) {
		 this.setStatus(`Hardware write error: ${error.message}`);
		 return false;
		}
	}

	async openDrawer() {
		if (await this.write(printerCommands.drawerPulse)) {
			this.setStatus('Cash drawer opened.');
		}
	}

	async printReceipt(receipt) {
		this.lastReceipt = receipt;

		if (!this.writer && !this.usbDevice) {
		 this.setStatus('Sale saved. Connect the USB or serial printer to print.');
			return;
		}

		const encoder = new TextEncoder();
		const text = (value) => encoder.encode(`${value}\n`);
		const line = '-'.repeat(32);
		const itemLines = receipt.items.flatMap((item) => [
			text(this.fitLine(`${item.name} x${item.quantity}`, this.money(item.total))),
		]);
		const paymentLines = receipt.payments.map((payment) => text(this.fitLine(payment.name, this.money(payment.amount))));
		const tenderLines = receipt.tendered > 0
			? [text(this.fitLine('Tendered', this.money(receipt.tendered))), text(this.fitLine('Change', this.money(receipt.change)))]
			: [];

		const printed = await this.write(
			printerCommands.init,
			printerCommands.alignCenter,
			printerCommands.boldOn,
			text('POSZEN'),
			printerCommands.boldOff,
			text('SALES RECEIPT'),
			text(receipt.number),
			text(receipt.date),
			printerCommands.alignLeft,
			text(line),
			...itemLines,
			text(line),
			text(this.fitLine('Subtotal', this.money(receipt.subtotal))),
			text(this.fitLine('Discount', this.money(receipt.discount))),
			text(this.fitLine('TOTAL', this.money(receipt.total))),
			text(line),
			...paymentLines,
			...tenderLines,
			text(line),
			printerCommands.alignCenter,
			text('Thank you!'),
			text(''),
			text(''),
			printerCommands.cut,
		);

		if (!printed) {
		 return;
		}

		if (receipt.payments.some((payment) => payment.name.toLowerCase() === 'cash')) {
			await this.openDrawer();
		}

		this.setStatus('Receipt printed.');
	}

	fitLine(left, right) {
		const width = 32;
		const available = Math.max(1, width - right.length - 1);
		return `${left.slice(0, available).padEnd(available, ' ')} ${right}`;
	}

	money(value) {
		return `₱${Number(value || 0).toFixed(2)}`;
	}

	setStatus(status) {
		window.dispatchEvent(new CustomEvent('pos:hardware-status', { detail: status }));
	}
}

window.posHardware = new PosHardware();
window.addEventListener('pos:open-cash-drawer', () => window.posHardware.openDrawer());

const initializeProductCategorySelect = () => {
	const getWire = (element) => {
		const componentRoot = element.closest(String.raw`[wire\:id]`);
		return componentRoot && window.Livewire?.find(componentRoot.getAttribute('wire:id'));
	};
	const categorySelect = document.getElementById('product-report-category');

	if (categorySelect && !$(categorySelect).hasClass('select2-hidden-accessible')) {
		const $categories = $(categorySelect);

		$categories.select2({
			allowClear: true,
			closeOnSelect: false,
			placeholder: 'All categories',
			width: '100%',
		});
		$categories.on('change', () => {
			const productSelect = document.getElementById('product-report-products');

			if (productSelect) {
				$(productSelect).empty().val(null).trigger('change.select2');
			}

			getWire(categorySelect)?.$set('productCategoryIds', $categories.val() || [], true);
		});
	}

	const favoriteProductSelect = document.getElementById('favorite-product-search');
	if (favoriteProductSelect && !$(favoriteProductSelect).hasClass('select2-hidden-accessible')) {
		const $favoriteProducts = $(favoriteProductSelect);
		$favoriteProducts.select2({
			ajax: {
				url: favoriteProductSelect.dataset.searchUrl,
				dataType: 'json',
				delay: 250,
				data: (params) => ({ term: params.term || '', excludeTrackedFavorites: 1 }),
			},
			closeOnSelect: false,
			minimumInputLength: 2,
			placeholder: 'Search by product name or code...',
			width: '100%',
		});
		$favoriteProducts.on('change', () => {
			getWire(favoriteProductSelect)?.$set('selectedProductIds', $favoriteProducts.val() || [], true);
		});
		Livewire.on('favorite-products-added', () => {
			$favoriteProducts.val(null).trigger('change.select2');
		});
	}

	const productSelect = document.getElementById('product-report-products');

	if (!productSelect || $(productSelect).hasClass('select2-hidden-accessible')) {
		return;
	}

	const $products = $(productSelect);
	$products.select2({
		ajax: {
			url: productSelect.dataset.searchUrl,
			dataType: 'json',
			delay: 250,
			data: (params) => ({
				term: params.term || '',
				categories: $('#product-report-category').val() || [],
			}),
		},
		closeOnSelect: false,
		minimumInputLength: 2,
		placeholder: 'Search by product name or code...',
		width: '100%',
	});
	$products.on('change', () => {
		getWire(productSelect)?.$set('selectedProductIds', $products.val() || [], true);
	});
};

document.addEventListener('livewire:init', initializeProductCategorySelect, { once: true });
window.addEventListener('load', initializeProductCategorySelect, { once: true });

document.addEventListener('livewire:init', () => {
	Livewire.on('loyalty-card-number-generated', ({ code }) => {
		const barcode = document.getElementById('loyalty-card-barcode');

		if (barcode) {
			JsBarcode(barcode, code, {
				format: 'EAN13',
				height: 56,
				margin: 8,
				fontSize: 14,
				textMargin: 4,
			});
		}
	});

	const sizeTrendChartForLabels = (canvas, labelCount) => {
		const chartContent = canvas.parentElement;
		const scrollContainer = chartContent?.parentElement;

		if (!chartContent || !scrollContainer) {
			return;
		}

		chartContent.style.width = `${Math.max(scrollContainer.clientWidth, labelCount * 64)}px`;
	};
	const createCurrencyChart = (canvas, label, color) => {
		const currency = new Intl.NumberFormat('en-PH', {
			style: 'currency',
			currency: 'PHP',
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		});
		const compactCurrency = new Intl.NumberFormat('en-PH', {
			style: 'currency',
			currency: 'PHP',
			maximumFractionDigits: 0,
		});
		const labels = JSON.parse(canvas.dataset.labels || '[]');
		const values = JSON.parse(canvas.dataset.values || '[]');
		const chart = new Chart(canvas, {
			type: 'line',
			data: {
				labels,
				datasets: [{
					label,
					data: values,
					borderColor: color,
					backgroundColor: color,
					borderWidth: 2,
					pointRadius: 2,
					pointHoverRadius: 5,
					tension: 0.25,
					fill: false,
				}],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				layout: { padding: { top: 16 } },
				plugins: {
					legend: { display: false },
					datalabels: {
						align: 'top',
						anchor: 'end',
						color: '#475569',
						font: { size: 10, weight: '600' },
						formatter: (value) => compactCurrency.format(Number(value || 0)),
						offset: 4,
					},
					tooltip: {
						callbacks: {
							label: (context) => currency.format(Number(context.parsed.y || 0)),
						},
					},
				},
				scales: {
					x: {
						grid: { display: false },
						border: { display: false },
						ticks: { maxRotation: 0, autoSkip: true },
					},
					y: {
						beginAtZero: true,
						grid: { color: 'rgba(148, 163, 184, 0.18)' },
						border: { display: false },
						ticks: { callback: (value) => currency.format(Number(value)) },
					},
				},
			},
			plugins: [ChartDataLabels],
		});

		return chart;
	};

	const salesCanvas = document.getElementById('sales-trend-chart');
	const profitCanvas = document.getElementById('sales-profit-trend-chart');
	if (salesCanvas && profitCanvas) {
		const salesChart = createCurrencyChart(salesCanvas, 'Sales', '#059669');
		const profitChart = createCurrencyChart(profitCanvas, 'Profit', '#d97706');

		Livewire.on('sales-trend-updated', ({ labels, salesValues, profitValues }) => {
			for (const [canvas, chart] of [[salesCanvas, salesChart], [profitCanvas, profitChart]]) {
				sizeTrendChartForLabels(canvas, labels.length);
				chart.data.labels = labels;
				chart.resize();
			}
			salesChart.data.datasets[0].data = salesValues;
			profitChart.data.datasets[0].data = profitValues;
			salesChart.update();
			profitChart.update();
		});
	}

	const favoriteProductsSalesCanvas = document.getElementById('favorite-products-sales-chart');
	const favoriteProductsProfitCanvas = document.getElementById('favorite-products-profit-chart');
	if (favoriteProductsSalesCanvas && favoriteProductsProfitCanvas) {
		const currency = new Intl.NumberFormat('en-PH', {
			style: 'currency',
			currency: 'PHP',
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		});
		const compactCurrency = new Intl.NumberFormat('en-PH', {
			style: 'currency',
			currency: 'PHP',
			maximumFractionDigits: 0,
		});
		const productColors = ['#059669', '#2563eb', '#e11d48', '#d97706', '#7c3aed', '#0891b2', '#4d7c0f', '#c026d3'];
		const formatDatasets = (datasets) => datasets.map((dataset) => {
			const color = productColors[dataset.productIndex % productColors.length];

			return {
				...dataset,
				borderColor: color,
				backgroundColor: color,
				pointStyle: 'circle',
				borderWidth: 2,
				pointRadius: 2,
				tension: 0.25,
				fill: false,
			};
		});
		const createFavoriteProductsChart = (canvas, datasets, metric) => new Chart(canvas, {
			type: 'line',
			data: {
				labels: JSON.parse(canvas.dataset.labels || '[]'),
				datasets: formatDatasets(datasets.filter((dataset) => dataset.metric === metric)),
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: true, position: 'bottom' },
					datalabels: {
						display: (context) => Number(context.dataset.data[context.dataIndex] || 0) !== 0,
						align: 'top',
						anchor: 'end',
						color: (context) => context.dataset.borderColor,
						font: { size: 9, weight: '600' },
						formatter: (value) => compactCurrency.format(Number(value || 0)),
						clamp: true,
					},
					tooltip: {
						callbacks: {
							label: (context) => `${context.dataset.label}: ${currency.format(Number(context.parsed.y || 0))}`,
						},
					},
				},
				scales: {
					x: { grid: { display: false }, border: { display: false } },
					y: {
						beginAtZero: true,
						grid: { color: 'rgba(148, 163, 184, 0.18)' },
						border: { display: false },
						ticks: { callback: (value) => currency.format(Number(value)) },
					},
				},
			},
			plugins: [ChartDataLabels],
		});
		const initialDatasets = JSON.parse(favoriteProductsSalesCanvas.dataset.datasets || '[]');
		const salesChart = createFavoriteProductsChart(favoriteProductsSalesCanvas, initialDatasets, 'sales');
		const profitChart = createFavoriteProductsChart(favoriteProductsProfitCanvas, initialDatasets, 'profit');

		Livewire.on('favorite-products-trend-updated', ({ labels, datasets }) => {
			for (const [canvas, chart] of [[favoriteProductsSalesCanvas, salesChart], [favoriteProductsProfitCanvas, profitChart]]) {
				sizeTrendChartForLabels(canvas, labels.length);
				chart.data.labels = labels;
				chart.resize();
			}
			salesChart.data.datasets = formatDatasets(datasets.filter((dataset) => dataset.metric === 'sales'));
			profitChart.data.datasets = formatDatasets(datasets.filter((dataset) => dataset.metric === 'profit'));
			salesChart.update();
			profitChart.update();
		});
	}

	const canvas = document.getElementById('product-sales-trend');
	if (canvas) {
		const colors = ['#059669', '#2563eb', '#e11d48', '#d97706', '#7c3aed', '#0891b2', '#4d7c0f', '#c026d3'];
		const productTrendChart = new Chart(canvas, {
			type: 'line',
			data: {
				labels: [],
				datasets: [],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: true, position: 'bottom' },
					tooltip: {
						callbacks: {
							label: (context) => `${context.dataset.label}: ${Number(context.parsed.y || 0).toLocaleString('en-PH')} units`,
						},
					},
				},
				scales: {
					x: {
						grid: { display: false },
						border: { display: false },
						ticks: { maxRotation: 0, autoSkip: true },
					},
					y: {
						type: 'linear',
						beginAtZero: true,
						grid: { color: 'rgba(148, 163, 184, 0.18)' },
						border: { display: false },
						title: { display: true, text: 'Quantity' },
						ticks: { callback: (value) => Number(value).toLocaleString('en-PH') },
					},
				},
			},
		});

		Livewire.on('product-report-updated', ({ labels, datasets }) => {
			sizeTrendChartForLabels(canvas, labels.length);
			productTrendChart.data.labels = labels;
			productTrendChart.data.datasets = datasets.map((dataset, index) => ({
				...dataset,
				borderColor: colors[index % colors.length],
				backgroundColor: colors[index % colors.length],
				borderWidth: 2,
				pointRadius: 2,
				pointHoverRadius: 4,
				tension: 0.25,
				fill: false,
			}));
			productTrendChart.resize();
			productTrendChart.update();
		});
	}

	Livewire.on('pos-sale-completed', ({ receipt }) => window.posHardware.printReceipt(receipt));
});
