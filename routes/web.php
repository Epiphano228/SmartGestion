<?php

use App\Http\Controllers\DocumentPdfController;
use App\Http\Controllers\ExportController;
use App\Livewire\Auth\Login;
use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Documents\Form as DocumentForm;
use App\Livewire\Documents\Index as DocumentsIndex;
use App\Livewire\Payments\Index as PaymentsIndex;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/connexion', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/clients', ClientsIndex::class)->name('clients.index');
    Route::get('/categories', CategoriesIndex::class)->name('categories.index');
    Route::get('/produits', ProductsIndex::class)->name('products.index');
    Route::get('/documents/{type}', DocumentsIndex::class)->whereIn('type', ['invoice', 'proforma', 'quotation'])->name('documents.index');
    Route::get('/documents/{type}/nouveau', DocumentForm::class)->whereIn('type', ['invoice', 'proforma', 'quotation'])->name('documents.create');
    Route::get('/documents/{type}/{document}/modifier', DocumentForm::class)->whereIn('type', ['invoice', 'proforma', 'quotation'])->name('documents.edit');
    Route::get('/document/{document}/pdf', DocumentPdfController::class)->name('documents.pdf');
    Route::get('/paiements', PaymentsIndex::class)->name('payments.index');
    Route::get('/statistiques', ReportsIndex::class)->name('reports.index');
    Route::get('/parametres', SettingsIndex::class)->name('settings.index');
    Route::get('/exports/factures.csv', [ExportController::class, 'invoices'])->name('exports.invoices');
    Route::post('/deconnexion', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');
});
