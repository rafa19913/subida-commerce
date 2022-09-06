<?php 
namespace App\Exports;
 
use App\Models\Student;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use DB;
 
class StudentExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */ 
    public function headings():array{
        return[
            'id_producto_syscom',
            'titulo',
            'modelo',
            'stock',
            'precio',
            'img_portada',
            'marca',
        ];
    } 
    public function collection()
    {
     // $categoriasDeBD = DB::table('categories')
 
        return DB::table('productos_ml')->select('id_producto_syscom','titulo','modelo','stock','precio','img_portada','marca')->get();
        // return Student::all();
    }
}