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

document.addEventListener('livewire:init', () => {
	Livewire.on('pos-sale-completed', ({ receipt }) => window.posHardware.printReceipt(receipt));
});
