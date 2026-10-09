<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Contacts extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $search = trim($this->search);

        $contacts = DB::table('Customer')
            ->where(function ($query): void {
                $query->where('IsCustomer', 1)
                    ->orWhere('IsSupplier', 1);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('Name', 'like', $term)
                        ->orWhere('Email', 'like', $term)
                        ->orWhere('PhoneNumber', 'like', $term);
                });
            })
            ->orderBy('Name')
            ->paginate(20);

        return view('livewire.contacts', compact('contacts'));
    }
}
