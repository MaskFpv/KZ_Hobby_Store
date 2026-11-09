<?php

namespace App\Http\Controllers;

use App\Exports\BrandExport;
use App\Http\Controllers\Concerns\HandlesExcelImport;
use App\Http\Requests\BrandRequest;
use App\Imports\BrandImport;
use App\Models\Brand;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class BrandController extends Controller
{
    use HandlesExcelImport;

    public function index(): View
    {
        $this->authorize('view-any', Brand::class);

        $brands = Brand::withCount('products')->orderBy('name')->get();

        return view('app.brands.index', compact('brands'));
    }

    public function create(): View
    {
        $this->authorize('create', Brand::class);

        return view('app.brands.create');
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $this->authorize('create', Brand::class);

        $data = $request->safe()->except(['logo', 'remove_logo']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        Brand::create($data);

        return redirect()->route('brands.index')->withSuccess(__('crud.common.created'));
    }

    public function show(Brand $brand): RedirectResponse
    {
        return redirect()->route('brands.edit', $brand);
    }

    public function edit(Brand $brand): View
    {
        $this->authorize('update', $brand);

        return view('app.brands.edit', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->authorize('update', $brand);

        $data = $request->safe()->except(['logo', 'remove_logo']);

        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }
            $data['logo'] = $request->hasFile('logo')
                ? $request->file('logo')->store('brands', 'public')
                : null;
        }

        $brand->update($data);

        return redirect()->route('brands.index')->withSuccess(__('crud.common.saved'));
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);

        if ($brand->products()->exists()) {
            return redirect()->route('brands.index')
                ->with('error', "Brand {$brand->name} masih dipakai oleh produk, tidak bisa dihapus.");
        }

        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();

        return redirect()->route('brands.index')->withSuccess(__('crud.common.removed'));
    }

    public function export()
    {
        $this->authorize('view-any', Brand::class);

        return Excel::download(new BrandExport, date('Ymd') . '__Brand.xlsx');
    }

    public function exportpdf()
    {
        $this->authorize('view-any', Brand::class);

        $brands = Brand::withCount('products')->orderBy('name')->get();

        return Pdf::loadView('app.brands.data', compact('brands'))->download('brand.pdf');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Brand::class);

        return $this->runImport($request, new BrandImport, 'brands.index');
    }
}
