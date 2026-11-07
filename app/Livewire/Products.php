<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Products extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortField = 'Name';

    public string $sortDirection = 'asc';

    public bool $printingCatalog = false;

    public string $catalogPrintSize = 'thermal';

    public function prepareCatalogPrint(string $paperSize = 'thermal'): void
    {
        $this->catalogPrintSize = in_array($paperSize, ['thermal', 'a4'], true) ? $paperSize : 'thermal';
        $this->printingCatalog = true;
    }

    public function finishCatalogPrint(): void
    {
        $this->printingCatalog = false;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $allowedFields = ['Code', 'Name', 'Price', 'Cost', 'IsEnabled'];

        if (! in_array($field, $allowedFields, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render(): View
    {
        $search = trim($this->search);
        $sortColumns = [
            'Code' => 'product.Code',
            'Name' => 'product.Name',
            'Price' => 'product.Price',
            'Cost' => 'product.Cost',
            'IsEnabled' => 'product.IsEnabled',
        ];
        $sortColumn = $sortColumns[$this->sortField] ?? 'product.Name';

        $products = DB::table('Product as product')
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('product.Name', 'like', $term)
                        ->orWhere('product.Code', 'like', $term)
                        ->orWhereExists(function ($barcodeQuery) use ($term): void {
                            $barcodeQuery->selectRaw('1')
                                ->from('Barcode as barcode')
                                ->whereColumn('barcode.ProductId', 'product.Id')
                                ->where('barcode.Value', 'like', $term);
                        });
                });
            })
            ->orderBy($sortColumn, $this->sortDirection === 'desc' ? 'desc' : 'asc')
            ->orderBy('product.Id')
            ->paginate(20);

        $printProducts = $this->printingCatalog
            ? DB::table('Product')->select('Name', 'Price')->orderBy('Name')->orderBy('Id')->get()
            : collect();

        return view('livewire.products', compact('products', 'printProducts'));
    }
}
