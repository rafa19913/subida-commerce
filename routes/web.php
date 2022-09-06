<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('dashboard');
});

Route::get('dashboard', function () {
    return view('dashboard');
})->name('dash.index');



Route::get('actualizar', function () {
    return view('actualizar');
})->name('actualizar-bd');

Route::get('subir-a-tienda', function () {
    return view('subir-tienda');
})->name('subir-a-tienda');


Route::get('subir-a-mercado-libre', function () {
    return view('subir-mercado-libre');
})->name('subir-mercado-libre');





// Route::get('actualizar', [DashboardController::class, 'index'])->name('actualizar-bd');

Route::get('eliminar-registros', [DashboardController::class, 'eliminarRegistros'])->name('eliminar.registros');
Route::get('subir-videovigilancia', [DashboardController::class, 'subirVideovigilancia'])->name('subir.videovigilancia');
Route::get('subir-radiocomunicacion', [DashboardController::class, 'subirRadiocomunicacion'])->name('subir-radiocomunicacion');
Route::get('subir-redes-audio', [DashboardController::class, 'subirRedesAudio'])->name('subir-redes-audio');
Route::get('subir-iot-gps', [DashboardController::class, 'subirIotGps'])->name('subir-iot-gps');
Route::get('subir-deteccion-fuego', [DashboardController::class, 'subirDeteccionFuego'])->name('subir.deteccion-fuego');
Route::get('subir-automatizacion', [DashboardController::class, 'subirAutomaizacion'])->name('subir-automatizacion-intrusion');
Route::get('subir-control-acceso', [DashboardController::class, 'subirControlAcceso'])->name('subir-control-acceso');
Route::get('subir-energia', [DashboardController::class, 'subirEnergia'])->name('subir-energia');
Route::get('subir-cableado', [DashboardController::class, 'subirCableado'])->name('subir-cableado');


Route::get('eliminar-registros-tienda', [DashboardController::class, 'eliminarProductosTienda'])->name('eliminar.productos.tienda');



Route::get('my-example', [DashboardController::class, 'index'])->name('my.example');

Route::get('exportar-excel', [DashboardController::class, 'exportExcel'])->name('export-excell');



Route::get('acutalizar-bd-mercadolibre/{idSyscom}', [DashboardController::class, 'actualizarBDMercadoLibre','idSyscom'])->name('actualizar-bd-mercado-libre');

Route::get('eliminar-base-datos-mercadolibre', [DashboardController::class, 'eliminarBDMercadoLibre'])->name('delete-bd-mercado-libre');




Route::get('crear-xls', [DashboardController::class, 'crearExcelMercadoLibre'])->name('crear-xls');

