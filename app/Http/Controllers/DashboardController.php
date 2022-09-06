<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client as ClientGuzz;
use DB;
use Automattic\WooCommerce\Client;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentExport;

// DB::table('productos')->delete();

class DashboardController extends Controller{

    private function returnPriceDescuento($priceString){
        $price = (float)$priceString;
        $price = $price * 21; // Tipo de cambio aprox a de USD a MXN
        $price = $price * 1.15; // Mercado libre comision
        $price = $price * 1.35; // Margen ganacia
        $price = $price * 1.16; // IVA
        $price = $price + 150; // Costo aprox de envio
        
        $price = number_format((float)$price, 2, '.', ''); 
        return $price;
    }
    
    
    
    
    private function regresar60CaracteresDeUnString($titulo){
        $cadena = substr($titulo, 0, 60); 
        return $cadena;
    }
    

    public function eliminarBDMercadoLibre(){
        DB::table('productos_ml')->delete();
        return redirect()->route('subir-mercado-libre')->with('subida', 'Base de datos limpiada');

    }

    public function actualizarBDMercadoLibre($idSyscom){
 
        

        $cpanel_host = 'https://servidor3348.tl.controladordns.com:2083';
        $username = 'sydtcomm';
        $password = '4dm1nVITO2021!';
 
        

        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 5,
            'timeout'  => 5,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);


        
        $response = $client->request('GET', 'productos?stock=true&categoria='.$idSyscom)->getBody()->getContents();
        $data = json_decode($response, true);
        $cantidadPaginas = $data['paginas'];

        for ($i=0; $i < $cantidadPaginas; $i++) { 
            
            $response2 = $client->request('GET', 'productos?stock=true&categoria='.$idSyscom.'&pagina='.($i+1))->getBody()->getContents();
            $data2 = json_decode($response2, true);

            $cantidadProductos = count($data2['productos']); 
            
            for ($j=0; $j < $cantidadProductos; $j++) { 
                if ($data2['productos'][$j]['precios'] != null && $data2['productos'][$j]['categorias'] != null && $data2['productos'][$j]['img_portada'] != null && $data2['productos'][$j]['total_existencia'] > 15){
                    
              $urlImg = $data2['productos'][$j]['img_portada'];
                        
              $cpanel_host = 'https://servidor3348.tl.controladordns.com:2083';
              $username = 'sydtcomm';
              $password = '4dm1nVITO2021!';
               
              $img = file_get_contents($urlImg);
              $im = imagecreatefromstring($img);
              $width = imagesx($im);
              $height = imagesy($im);
              $newwidth = '1200';
              $newheight = '1200';
              $thumb = imagecreatetruecolor($newwidth, $newheight);
              imagecopyresized($thumb, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
              imagepng($thumb,'imagen01'.$j.'.png'); //save image as png
              imagedestroy($thumb); 
              imagedestroy($im);
              
       
          
              $curl = curl_init();
              $upload_file = realpath('imagen01'.$j.'.png'); 
              $destination_dir = "/home/sydtcomm/public_html/home2/sydtcomm/public_html/home/sydtcomm/public_html/sample_html/img-example";
              if (function_exists('curl_file_create')) {
                  $cf = curl_file_create($upload_file);
              } else {
                  $cf = "@/" . $upload_file;
              }
              $payload = array(
                  'dir' => $destination_dir,
                  'file-1' => $cf
              );
          
              $actionUrl = $cpanel_host . "/execute/Fileman/upload_files";
              curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);       // Allow self-signed certs
              curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);       // Allow certs that do not match the hostname
              curl_setopt($curl, CURLOPT_HEADER, 0);               // Do not include header in output
              curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);       // Return contents of transfer on curl_exec
              $header[0] = "Authorization: Basic " . base64_encode($username . ":" . $password) . "\n\r";
              curl_setopt($curl, CURLOPT_HTTPHEADER, $header);    // set the username and password
              curl_setopt($curl, CURLOPT_URL, $actionUrl);        // execute the query
          
              // Set up a POST request with the payload.
              curl_setopt($curl, CURLOPT_POST, true);
              curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
              curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          
              $result = curl_exec($curl);
              if ($result == false) {
                  error_log("curl_exec threw error \"" . curl_error($curl) . "\" for $actionUrl");
                  // log error if curl exec fails
              }
              curl_close($curl);
              
              $auxLink = 'http://vitotechnologies.com/img-example/imagen01'.$j.'.png';
            



                    $precio = $data2['productos'][$j]['precios']['precio_descuento'];
                    $precioFinal = $this->returnPriceDescuento($precio);

                    $tituloCompleto = $data2['productos'][$j]['titulo'];
                    $titulo60Caracteres = $this->regresar60CaracteresDeUnString($tituloCompleto);
              
                    $titulofinal = mb_strtolower($titulo60Caracteres);

                    // mb_strtolower($nome);
                    
                    DB::table('productos_ml')
                    ->insert([
                        'id_producto_syscom' => $data2['productos'][$j]['producto_id'],
                        'titulo' => $titulofinal,
                        'modelo' => $data2['productos'][$j]['modelo'],
                        'stock' => $data2['productos'][$j]['total_existencia'],
                        'precio' => $precioFinal,
                        'img_portada' => $auxLink,
                        'marca' => $data2['productos'][$j]['marca'],
                    ]);

                }

            }



        }
        
        

        return redirect()->route('subir-mercado-libre')->with('subida', 'Subida correcta a BD -> IDs >'.$idSyscom);


    }

    public function crearExcelMercadoLibre(){
 // // $users = json_decode($users, true);

 return Excel::download(new StudentExport, 'students.xlsx');
}


    public function exportExcel(){

        // $users = DB::table('users')
        //     ->select('*')
        //     ->get();
        // // $users = json_decode($users, true);

        return Excel::download(new StudentExport, 'students.xlsx');
        

    }

    private function cargarCategorias(){
        $categoriasDeBD = DB::table('categories')
            ->select('*')
            ->where('nivel', '=', '1')
            ->get();
       $categoriasDeBD = json_decode($categoriasDeBD, true);
 
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'timeout'  => 10.0,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);



        $response = $client->request('GET', 'categorias/')->getBody()->getContents();
        $data = json_decode($response, true);

        $cantidadCat = count($data);
        

        for ($i=0; $i <$cantidadCat; $i++) {
            DB::table('categories')
                    ->insert([
                        'id_categoria_syscom' => $data[$i]['id'],
                        'nombre' => $data[$i]['nombre'],
                        'nivel' => $data[$i]['nivel'],
                        'id_categoria_padre' => null,
                    ]);
        }


        $categoriasDeBD = DB::table('categories')
            ->select('*')
            ->where('nivel', '=', '1')
            ->get();
        $categoriasDeBD = json_decode($categoriasDeBD, true);
     
        // return count($categoriasDeBD);

        for ($i=0; $i < $cantidadCat; $i++) { 
            $idSyscom = $categoriasDeBD[$i]['id_categoria_syscom'];
            $idPadre = $categoriasDeBD[$i]['id'];
            $response = $client->request('GET', 'categorias/'.$idSyscom)->getBody()->getContents();
            $data = json_decode($response, true);

            $cantidadSubC = count($data['subcategorias']);
            for ($j=0; $j<$cantidadSubC; $j++){
                DB::table('categories')
                        ->insert([
                            'id_categoria_syscom' => $data['subcategorias'][$j]['id'],
                            'nombre' => $data['subcategorias'][$j]['nombre'],
                            'nivel' => $data['subcategorias'][$j]['nivel'],
                            'id_categoria_padre' => $idPadre,
                        ]);
            }

        }



        $categoriasDeBD = DB::table('categories')
            ->select('*')
            ->where('nivel', '=', '2')
            ->get();
        $categoriasDeBD = json_decode($categoriasDeBD, true);

        $cantidadCat = count($categoriasDeBD);
     
        // return count($categoriasDeBD);

        for ($i=0; $i < $cantidadCat; $i++) { 
            $idSyscom = $categoriasDeBD[$i]['id_categoria_syscom'];
            $idPadre = $categoriasDeBD[$i]['id'];
            $response = $client->request('GET', 'categorias/'.$idSyscom)->getBody()->getContents();
            $data = json_decode($response, true);

            $cantidadSubC = count($data['subcategorias']);
            for ($j=0; $j<$cantidadSubC; $j++){
                DB::table('categories')
                        ->insert([
                            'id_categoria_syscom' => $data['subcategorias'][$j]['id'],
                            'nombre' => $data['subcategorias'][$j]['nombre'],
                            'nivel' => $data['subcategorias'][$j]['nivel'],
                            'id_categoria_padre' => $idPadre,
                        ]);
            }

        }

    }

    private function cargarMarcas(){
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'timeout'  => 10.0,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);

        

        $response = $client->request('GET', 'marcas/')->getBody()->getContents();
        $data = json_decode($response, true);

        
        for ($i=0; $i < count($data); $i++) { 

            $response2 = $client->request('GET', 'marcas/'.$data[$i]['id'])->getBody()->getContents();
            $data2 = json_decode($response2, true);

            DB::table('marcas')
            ->insert([
                'id_marca_syscom' => $data[$i]['id'],
                'nombre' => $data[$i]['nombre'],
                'logo' => $data2['logo']
            ]);


        }
        
    }

    private function calcularPrecio($precioDescuento){

        if ($precioDescuento == null){
            return '$0';
        }
        
        $precioDescuentoToInt = intval($precioDescuento);
    
        $precioFinal = $precioDescuentoToInt * 21; // Lo sacamos al tipo de cambio NORMAL
        $precioFinal = $precioFinal * 1.35;
        return '$'.$precioFinal;
    }

    public function eliminarRegistros(){
        DB::table('productos')->delete();
        DB::table('img_productos')->delete();
        DB::table('imagenes')->delete();

        return redirect()->route('actualizar-bd')->with('eliminacion', 'Eliminacion correcta');
    }

    public function subirVideovigilancia(){

        
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 10,
            'timeout'  => 10,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);
        


        $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_a2cec0ee2b4f6cf41d7a16cb0d439cb2f1add4ca',
            'cs_7e872fa851d450f67e0c5a183488f091b9194b40',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );

        $response0 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=22&')->getBody()->getContents();
        $data0 = json_decode($response0, true);

        
        
        
        for ($i=0; $i < $data0['paginas']; $i++) { 
            $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=22&pagina='.$i+1)->getBody()->getContents();
            $data2 = json_decode($response2, true);

            for ($j=0; $j < count($data2['productos']) ; $j++) { 
                $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
                $data3 = json_decode($response3, true);            
                if ($data3['precios'] != null && $data3['categorias'] != null){
                    $categoriaProducto = DB::table('categories')
                        ->select('*')
                        ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
                        ->get();
                    $categoriaProducto = json_decode($categoriaProducto, true);

                    DB::table('productos')
                        ->insert([
                            'id_producto_syscom' => $data3['producto_id'],
                            'titulo' => $data3['titulo'],
                            'modelo' => $data3['modelo'],
                            'stock' => $data3['total_existencia'],
                            'precio' => $data3['precios']['precio_descuento'],
                            'descripcion' => $data3['descripcion'],
                            'img_portada' => $data3['img_portada'],
                            'marca' => $data3['marca'],
                            'id_categoria' => $categoriaProducto[0]['id']
                        ]);

                    if ($data3['imagenes'] != null && count($data3['imagenes']) > 1 ){
                        $idProducto = DB::table('productos')
                        ->select('*')
                        ->where('id_producto_syscom', '=', $data3['producto_id'])
                        ->get();
                       $idProducto = json_decode($idProducto, true);
                       

                      for ($k=1; $k <count($data3['imagenes']) ; $k++) { 
                           DB::table('imagenes')
                           ->insert([
                              'imagen' => $data3['imagenes'][$k]['imagen']
                           ]);

                          $idImg = DB::table('imagenes')
                           ->select('*')
                           ->where('imagen', '=', $data3['imagenes'][$k]['imagen'])
                           ->get();
                          $idImg = json_decode($idImg, true);

                          DB::table('img_productos')
                          ->insert([
                            'id_producto' => $idProducto[0]['id'],
                            'id_imagen' => $idImg[0]['id']
                          ]);
                      }
                    }
                }
            }

        }

        
        return redirect()->route('actualizar-bd')->with('subida', 'Subida de videovigilancia correctamente');

        // FINALIZA SUBIDA DE PRODUCTOS VIDEOVIGILANCIA CON IMAGENES


        
    }
    
    public function subirRadiocomunicacion(){

        
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 10,
            'timeout'  => 10,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);

        
        $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_a2cec0ee2b4f6cf41d7a16cb0d439cb2f1add4ca',
            'cs_7e872fa851d450f67e0c5a183488f091b9194b40',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );

        $response0 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=25&')->getBody()->getContents();
        $data0 = json_decode($response0, true);

        
        
        
        for ($i=0; $i < $data0['paginas']; $i++) { 
            $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=25&pagina='.$i+1)->getBody()->getContents();
            $data2 = json_decode($response2, true);

            for ($j=0; $j < count($data2['productos']) ; $j++) { 
                $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
                $data3 = json_decode($response3, true);            
                if ($data3['precios'] != null && $data3['categorias'] != null){
                    $categoriaProducto = DB::table('categories')
                        ->select('*')
                        ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
                        ->get();
                    $categoriaProducto = json_decode($categoriaProducto, true);

                    DB::table('productos')
                        ->insert([
                            'id_producto_syscom' => $data3['producto_id'],
                            'titulo' => $data3['titulo'],
                            'modelo' => $data3['modelo'],
                            'stock' => $data3['total_existencia'],
                            'precio' => $data3['precios']['precio_descuento'],
                            'descripcion' => $data3['descripcion'],
                            'img_portada' => $data3['img_portada'],
                            'marca' => $data3['marca'],
                            'id_categoria' => $categoriaProducto[0]['id']
                        ]);

                    if ($data3['imagenes'] != null && count($data3['imagenes']) > 1 ){
                        $idProducto = DB::table('productos')
                        ->select('*')
                        ->where('id_producto_syscom', '=', $data3['producto_id'])
                        ->get();
                       $idProducto = json_decode($idProducto, true);
                       

                      for ($k=1; $k <count($data3['imagenes']) ; $k++) { 
                           DB::table('imagenes')
                           ->insert([
                              'imagen' => $data3['imagenes'][$k]['imagen']
                           ]);

                          $idImg = DB::table('imagenes')
                           ->select('*')
                           ->where('imagen', '=', $data3['imagenes'][$k]['imagen'])
                           ->get();
                          $idImg = json_decode($idImg, true);

                          DB::table('img_productos')
                          ->insert([
                            'id_producto' => $idProducto[0]['id'],
                            'id_imagen' => $idImg[0]['id']
                          ]);
                      }
                    }
                }
            }

        }
        
        return redirect()->route('actualizar-bd')->with('subida', 'Subida de radiocomunicacion correctamente');

        // FINALIZA SUBIDA DE PRODUCTOS VIDEOVIGILANCIA CON IMAGENES


        
    }
    
    public function subirRedesAudio(){

        
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 10,
            'timeout'  => 10,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);
        

        $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_a2cec0ee2b4f6cf41d7a16cb0d439cb2f1add4ca',
            'cs_7e872fa851d450f67e0c5a183488f091b9194b40',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );

        $response0 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=26&')->getBody()->getContents();
        $data0 = json_decode($response0, true);

        
        
        
        for ($i=0; $i < $data0['paginas']; $i++) { 
            $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=26&pagina='.$i+1)->getBody()->getContents();
            $data2 = json_decode($response2, true);

            for ($j=0; $j < count($data2['productos']) ; $j++) { 
                $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
                $data3 = json_decode($response3, true);            
                if ($data3['precios'] != null && $data3['categorias'] != null){
                    $categoriaProducto = DB::table('categories')
                        ->select('*')
                        ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
                        ->get();
                    $categoriaProducto = json_decode($categoriaProducto, true);

                    DB::table('productos')
                        ->insert([
                            'id_producto_syscom' => $data3['producto_id'],
                            'titulo' => $data3['titulo'],
                            'modelo' => $data3['modelo'],
                            'stock' => $data3['total_existencia'],
                            'precio' => $data3['precios']['precio_descuento'],
                            'descripcion' => $data3['descripcion'],
                            'img_portada' => $data3['img_portada'],
                            'marca' => $data3['marca'],
                            'id_categoria' => $categoriaProducto[0]['id']
                        ]);

                    if ($data3['imagenes'] != null && count($data3['imagenes']) > 1 ){
                        $idProducto = DB::table('productos')
                        ->select('*')
                        ->where('id_producto_syscom', '=', $data3['producto_id'])
                        ->get();
                       $idProducto = json_decode($idProducto, true);
                       

                      for ($k=1; $k <count($data3['imagenes']) ; $k++) { 
                           DB::table('imagenes')
                           ->insert([
                              'imagen' => $data3['imagenes'][$k]['imagen']
                           ]);

                          $idImg = DB::table('imagenes')
                           ->select('*')
                           ->where('imagen', '=', $data3['imagenes'][$k]['imagen'])
                           ->get();
                          $idImg = json_decode($idImg, true);

                          DB::table('img_productos')
                          ->insert([
                            'id_producto' => $idProducto[0]['id'],
                            'id_imagen' => $idImg[0]['id']
                          ]);
                      }
                    }
                }
            }

        }
        
        return redirect()->route('actualizar-bd')->with('subida', 'Subida de redes y audio correctamente');

        // FINALIZA SUBIDA DE PRODUCTOS VIDEOVIGILANCIA CON IMAGENES


        
    }
    
    public function subirIotGps(){

        
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 10,
            'timeout'  => 10,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);
        

        $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_a2cec0ee2b4f6cf41d7a16cb0d439cb2f1add4ca',
            'cs_7e872fa851d450f67e0c5a183488f091b9194b40',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );

        $response0 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=27&')->getBody()->getContents();
        $data0 = json_decode($response0, true);

        
        
        
        for ($i=0; $i < $data0['paginas']; $i++) { 
            $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=27&pagina='.$i+1)->getBody()->getContents();
            $data2 = json_decode($response2, true);

            for ($j=0; $j < count($data2['productos']) ; $j++) { 
                $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
                $data3 = json_decode($response3, true);            
                if ($data3['precios'] != null && $data3['categorias'] != null){
                    $categoriaProducto = DB::table('categories')
                        ->select('*')
                        ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
                        ->get();
                    $categoriaProducto = json_decode($categoriaProducto, true);

                    DB::table('productos')
                        ->insert([
                            'id_producto_syscom' => $data3['producto_id'],
                            'titulo' => $data3['titulo'],
                            'modelo' => $data3['modelo'],
                            'stock' => $data3['total_existencia'],
                            'precio' => $data3['precios']['precio_descuento'],
                            'descripcion' => $data3['descripcion'],
                            'img_portada' => $data3['img_portada'],
                            'marca' => $data3['marca'],
                            'id_categoria' => $categoriaProducto[0]['id']
                        ]);

                    if ($data3['imagenes'] != null && count($data3['imagenes']) > 1 ){
                        $idProducto = DB::table('productos')
                        ->select('*')
                        ->where('id_producto_syscom', '=', $data3['producto_id'])
                        ->get();
                       $idProducto = json_decode($idProducto, true);
                       

                      for ($k=1; $k <count($data3['imagenes']) ; $k++) { 
                           DB::table('imagenes')
                           ->insert([
                              'imagen' => $data3['imagenes'][$k]['imagen']
                           ]);

                          $idImg = DB::table('imagenes')
                           ->select('*')
                           ->where('imagen', '=', $data3['imagenes'][$k]['imagen'])
                           ->get();
                          $idImg = json_decode($idImg, true);

                          DB::table('img_productos')
                          ->insert([
                            'id_producto' => $idProducto[0]['id'],
                            'id_imagen' => $idImg[0]['id']
                          ]);
                      }
                    }
                }
            }

        }
        
        return redirect()->route('actualizar-bd')->with('subida', 'Subida de IoT / GPS correctamente');

        // FINALIZA SUBIDA DE PRODUCTOS VIDEOVIGILANCIA CON IMAGENES


        
    }
    
    
    
    public function subirEnergia(){

        
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 10,
            'timeout'  => 10,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);


        $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_f62d80ab253b27b9903b375ec5a5394885b62f5d',
            'cs_977b67206423636c98d94e5dcfccee99f624fd34',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );
        
        $response0 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=30&')->getBody()->getContents();
        $data0 = json_decode($response0, true);

        
        
        
        for ($i=0; $i < $data0['paginas']; $i++) { 
            $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=30&pagina='.$i+1)->getBody()->getContents();
            $data2 = json_decode($response2, true);

            for ($j=0; $j < count($data2['productos']) ; $j++) { 
                $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
                $data3 = json_decode($response3, true);            
                if ($data3['precios'] != null && $data3['categorias'] != null){
                    $categoriaProducto = DB::table('categories')
                        ->select('*')
                        ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
                        ->get();
                    $categoriaProducto = json_decode($categoriaProducto, true);

                    DB::table('productos')
                        ->insert([
                            'id_producto_syscom' => $data3['producto_id'],
                            'titulo' => $data3['titulo'],
                            'modelo' => $data3['modelo'],
                            'stock' => $data3['total_existencia'],
                            'precio' => $data3['precios']['precio_descuento'],
                            'descripcion' => $data3['descripcion'],
                            'img_portada' => $data3['img_portada'],
                            'marca' => $data3['marca'],
                            'id_categoria' => $categoriaProducto[0]['id']
                        ]);

                    if ($data3['imagenes'] != null && count($data3['imagenes']) > 1 ){
                        $idProducto = DB::table('productos')
                        ->select('*')
                        ->where('id_producto_syscom', '=', $data3['producto_id'])
                        ->get();
                       $idProducto = json_decode($idProducto, true);
                       

                      for ($k=1; $k <count($data3['imagenes']) ; $k++) { 
                           DB::table('imagenes')
                           ->insert([
                              'imagen' => $data3['imagenes'][$k]['imagen']
                           ]);

                          $idImg = DB::table('imagenes')
                           ->select('*')
                           ->where('imagen', '=', $data3['imagenes'][$k]['imagen'])
                           ->get();
                          $idImg = json_decode($idImg, true);

                          DB::table('img_productos')
                          ->insert([
                            'id_producto' => $idProducto[0]['id'],
                            'id_imagen' => $idImg[0]['id']
                          ]);
                      }
                    }
                }
            }

        }
        
        return redirect()->route('actualizar-bd')->with('subida', 'Subida de energia correctamente');

        // FINALIZA SUBIDA DE PRODUCTOS VIDEOVIGILANCIA CON IMAGENES


        
    }
    
    
    public function subirAutomaizacion(){

        
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 10,
            'timeout'  => 10,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);

        
        $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_a2cec0ee2b4f6cf41d7a16cb0d439cb2f1add4ca',
            'cs_7e872fa851d450f67e0c5a183488f091b9194b40',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );

        $response0 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=32&')->getBody()->getContents();
        $data0 = json_decode($response0, true);

        
        
        
        for ($i=0; $i < $data0['paginas']; $i++) { 
            $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=32&pagina='.$i+1)->getBody()->getContents();
            $data2 = json_decode($response2, true);

            for ($j=0; $j < count($data2['productos']) ; $j++) { 
                $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
                $data3 = json_decode($response3, true);            
                if ($data3['precios'] != null && $data3['categorias'] != null){
                    $categoriaProducto = DB::table('categories')
                        ->select('*')
                        ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
                        ->get();
                    $categoriaProducto = json_decode($categoriaProducto, true);

                    DB::table('productos')
                        ->insert([
                            'id_producto_syscom' => $data3['producto_id'],
                            'titulo' => $data3['titulo'],
                            'modelo' => $data3['modelo'],
                            'stock' => $data3['total_existencia'],
                            'precio' => $data3['precios']['precio_descuento'],
                            'descripcion' => $data3['descripcion'],
                            'img_portada' => $data3['img_portada'],
                            'marca' => $data3['marca'],
                            'id_categoria' => $categoriaProducto[0]['id']
                        ]);

                    if ($data3['imagenes'] != null && count($data3['imagenes']) > 1 ){
                        $idProducto = DB::table('productos')
                        ->select('*')
                        ->where('id_producto_syscom', '=', $data3['producto_id'])
                        ->get();
                       $idProducto = json_decode($idProducto, true);
                       

                      for ($k=1; $k <count($data3['imagenes']) ; $k++) { 
                           DB::table('imagenes')
                           ->insert([
                              'imagen' => $data3['imagenes'][$k]['imagen']
                           ]);

                          $idImg = DB::table('imagenes')
                           ->select('*')
                           ->where('imagen', '=', $data3['imagenes'][$k]['imagen'])
                           ->get();
                          $idImg = json_decode($idImg, true);

                          DB::table('img_productos')
                          ->insert([
                            'id_producto' => $idProducto[0]['id'],
                            'id_imagen' => $idImg[0]['id']
                          ]);
                      }
                    }
                }
            }

        }
        
        return redirect()->route('actualizar-bd')->with('subida', 'Subida de automatizacion e intrusion correctamente');

        // FINALIZA SUBIDA DE PRODUCTOS VIDEOVIGILANCIA CON IMAGENES


        
    }
    
    
    public function subirControlAcceso(){

        
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 10,
            'timeout'  => 10,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);

        
        $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_a2cec0ee2b4f6cf41d7a16cb0d439cb2f1add4ca',
            'cs_7e872fa851d450f67e0c5a183488f091b9194b40',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );

        $response0 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=37&')->getBody()->getContents();
        $data0 = json_decode($response0, true);

        
        
        
        for ($i=0; $i < $data0['paginas']; $i++) { 
            $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=37&pagina='.$i+1)->getBody()->getContents();
            $data2 = json_decode($response2, true);

            for ($j=0; $j < count($data2['productos']) ; $j++) { 
                $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
                $data3 = json_decode($response3, true);            
                if ($data3['precios'] != null && $data3['categorias'] != null){
                    $categoriaProducto = DB::table('categories')
                        ->select('*')
                        ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
                        ->get();
                    $categoriaProducto = json_decode($categoriaProducto, true);

                    DB::table('productos')
                        ->insert([
                            'id_producto_syscom' => $data3['producto_id'],
                            'titulo' => $data3['titulo'],
                            'modelo' => $data3['modelo'],
                            'stock' => $data3['total_existencia'],
                            'precio' => $data3['precios']['precio_descuento'],
                            'descripcion' => $data3['descripcion'],
                            'img_portada' => $data3['img_portada'],
                            'marca' => $data3['marca'],
                            'id_categoria' => $categoriaProducto[0]['id']
                        ]);

                    if ($data3['imagenes'] != null && count($data3['imagenes']) > 1 ){
                        $idProducto = DB::table('productos')
                        ->select('*')
                        ->where('id_producto_syscom', '=', $data3['producto_id'])
                        ->get();
                       $idProducto = json_decode($idProducto, true);
                       

                      for ($k=1; $k <count($data3['imagenes']) ; $k++) { 
                           DB::table('imagenes')
                           ->insert([
                              'imagen' => $data3['imagenes'][$k]['imagen']
                           ]);

                          $idImg = DB::table('imagenes')
                           ->select('*')
                           ->where('imagen', '=', $data3['imagenes'][$k]['imagen'])
                           ->get();
                          $idImg = json_decode($idImg, true);

                          DB::table('img_productos')
                          ->insert([
                            'id_producto' => $idProducto[0]['id'],
                            'id_imagen' => $idImg[0]['id']
                          ]);
                      }
                    }
                }
            }

        }
        
        return redirect()->route('actualizar-bd')->with('subida', 'Subida de control de acceso correctamente');

        // FINALIZA SUBIDA DE PRODUCTOS VIDEOVIGILANCIA CON IMAGENES


        
    }
    
    public function subirDeteccionFuego(){

        
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 10,
            'timeout'  => 10,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);
        
        $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_a2cec0ee2b4f6cf41d7a16cb0d439cb2f1add4ca',
            'cs_7e872fa851d450f67e0c5a183488f091b9194b40',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );


        $response0 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=38&')->getBody()->getContents();
        $data0 = json_decode($response0, true);

        
        
        
        for ($i=0; $i < $data0['paginas']; $i++) { 
            $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=38&pagina='.$i+1)->getBody()->getContents();
            $data2 = json_decode($response2, true);

            for ($j=0; $j < count($data2['productos']) ; $j++) { 
                $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
                $data3 = json_decode($response3, true);            
                if ($data3['precios'] != null && $data3['categorias'] != null){
                    $categoriaProducto = DB::table('categories')
                        ->select('*')
                        ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
                        ->get();
                    $categoriaProducto = json_decode($categoriaProducto, true);

                    DB::table('productos')
                        ->insert([
                            'id_producto_syscom' => $data3['producto_id'],
                            'titulo' => $data3['titulo'],
                            'modelo' => $data3['modelo'],
                            'stock' => $data3['total_existencia'],
                            'precio' => $data3['precios']['precio_descuento'],
                            'descripcion' => $data3['descripcion'],
                            'img_portada' => $data3['img_portada'],
                            'marca' => $data3['marca'],
                            'id_categoria' => $categoriaProducto[0]['id']
                        ]);

                    if ($data3['imagenes'] != null && count($data3['imagenes']) > 1 ){
                        $idProducto = DB::table('productos')
                        ->select('*')
                        ->where('id_producto_syscom', '=', $data3['producto_id'])
                        ->get();
                       $idProducto = json_decode($idProducto, true);
                       

                      for ($k=1; $k <count($data3['imagenes']) ; $k++) { 
                           DB::table('imagenes')
                           ->insert([
                              'imagen' => $data3['imagenes'][$k]['imagen']
                           ]);

                          $idImg = DB::table('imagenes')
                           ->select('*')
                           ->where('imagen', '=', $data3['imagenes'][$k]['imagen'])
                           ->get();
                          $idImg = json_decode($idImg, true);

                          DB::table('img_productos')
                          ->insert([
                            'id_producto' => $idProducto[0]['id'],
                            'id_imagen' => $idImg[0]['id']
                          ]);
                      }
                    }
                }
            }

        }
        
        return redirect()->route('actualizar-bd')->with('subida', 'Subida de deteccion de fuego correctamente');

        // FINALIZA SUBIDA DE PRODUCTOS VIDEOVIGILANCIA CON IMAGENES


        
    }
    
    
    public function subirCableado(){

        
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 10,
            'timeout'  => 10,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);   $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_a2cec0ee2b4f6cf41d7a16cb0d439cb2f1add4ca',
            'cs_7e872fa851d450f67e0c5a183488f091b9194b40',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );

        $response0 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=65811&')->getBody()->getContents();
        $data0 = json_decode($response0, true);

        
        
        
        for ($i=0; $i < $data0['paginas']; $i++) { 
            $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=65811&pagina='.$i+1)->getBody()->getContents();
            $data2 = json_decode($response2, true);

            for ($j=0; $j < count($data2['productos']) ; $j++) { 
                $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
                $data3 = json_decode($response3, true);            
                if ($data3['precios'] != null && $data3['categorias'] != null){
                    $categoriaProducto = DB::table('categories')
                        ->select('*')
                        ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
                        ->get();
                    $categoriaProducto = json_decode($categoriaProducto, true);

                    DB::table('productos')
                        ->insert([
                            'id_producto_syscom' => $data3['producto_id'],
                            'titulo' => $data3['titulo'],
                            'modelo' => $data3['modelo'],
                            'stock' => $data3['total_existencia'],
                            'precio' => $data3['precios']['precio_descuento'],
                            'descripcion' => $data3['descripcion'],
                            'img_portada' => $data3['img_portada'],
                            'marca' => $data3['marca'],
                            'id_categoria' => $categoriaProducto[0]['id']
                        ]);

                    if ($data3['imagenes'] != null && count($data3['imagenes']) > 1 ){
                        $idProducto = DB::table('productos')
                        ->select('*')
                        ->where('id_producto_syscom', '=', $data3['producto_id'])
                        ->get();
                       $idProducto = json_decode($idProducto, true);
                       

                      for ($k=1; $k <count($data3['imagenes']) ; $k++) { 
                           DB::table('imagenes')
                           ->insert([
                              'imagen' => $data3['imagenes'][$k]['imagen']
                           ]);

                          $idImg = DB::table('imagenes')
                           ->select('*')
                           ->where('imagen', '=', $data3['imagenes'][$k]['imagen'])
                           ->get();
                          $idImg = json_decode($idImg, true);

                          DB::table('img_productos')
                          ->insert([
                            'id_producto' => $idProducto[0]['id'],
                            'id_imagen' => $idImg[0]['id']
                          ]);
                      }
                    }
                }
            }

        }
        
        return redirect()->route('actualizar-bd')->with('subida', 'Subida de cableado correctamente');

        // FINALIZA SUBIDA DE PRODUCTOS VIDEOVIGILANCIA CON IMAGENES


        
    }
    


    public function eliminarProductosTienda(){
        $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_a2cec0ee2b4f6cf41d7a16cb0d439cb2f1add4ca',
            'cs_7e872fa851d450f67e0c5a183488f091b9194b40',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );

        $page = 1;
        $products = [];
        $all_products = [];
        do{
        try {
            // $products = $wc->get('products',array('ordery' => 'id', 'per_page' => 100, 'page' => $page));
            $products = $woocommerce->get('products',array('per_page' => 100, 'page' => $page));
        }catch(HttpClientException $e){
            die("Can't get products: $e");
        }
        $all_products = array_merge($all_products,$products);
        // print_r($products);
        $page++;
        } while (count($products) > 0);

        $myOtherArray = json_decode(json_encode($all_products),true);

        for ($i=0; $i <count($myOtherArray); $i++) { 
            $woocommerce->delete('products/'.$myOtherArray[$i]['id'] , ['force' => true]);
        }

        return redirect()->route('subir-a-tienda')->with('eliminacion', 'Eliminacion de productos correctamente');


    }
    

    public function subirProductosATienda(){
          
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 2,
            'timeout'  => 2,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);

        $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_a2cec0ee2b4f6cf41d7a16cb0d439cb2f1add4ca',
            'cs_7e872fa851d450f67e0c5a183488f091b9194b40',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );

        $productos = DB::table('productos')
            ->select('*')
            ->where('stock', '>=', 80)
            ->get();
        $productos = json_decode($productos, true);


        for ($i=0; $i < count($productos) ; $i++) {
            $idCatBd = DB::table('categories')
                ->select('*')
                ->where('id', '=', $productos[$i]['id_categoria'])
                ->get();
            $idCatBd = json_decode($idCatBd, true);


            $myArray = $woocommerce->get('products/categories', ['slug' => $idCatBd[0]['id_categoria_syscom']]);
            $myOtherArray = json_decode(json_encode($myArray),true);
                
            $data = [
                'name' => $productos[$i]['titulo'],
                'regular_price' => $productos[$i]['precio'],
                'description' => $productos[$i]['descripcion'],
                'short_description' => $productos[$i]['modelo'],
                'slug' => $productos[$i]['id_producto_syscom'],
                'manage_stock' => true,
                'stock_quantity' => $productos[$i]['stock'],

                'categories' => [
                    [
                        'id' => (int) $myOtherArray[0]['id']
                    ]
                ]
            ];
            
                if ($productos[$i]['img_portada'] != null){
                    $idCatBd = DB::table('img_productos')
                    ->select('*')
                    ->where('id_producto', '=', $productos[$i]['id'])
                    ->get();
                $idCatBd = json_decode($idCatBd, true);
            
                // Agregando el data con imagenes
                    $newDataWithImages = array();
                    $newDataWithImages['images'][0]['src'] = $productos[$i]['img_portada'];

                    if (count($idCatBd) > 1){
                        for ($j=0; $j < count($idCatBd); $j++){
                            $imagen = DB::table('imagenes')
                                ->select('*')
                                ->where('id', '=', $idCatBd[$j]['id_imagen'])
                                ->get();
                            $imagen = json_decode($imagen, true);
                     
                            $newDataWithImages['images'][$j+1]['src'] = $imagen[0]['imagen'];
                        }
                    }
                    $dataWithImagesAdd = array_merge($data, $newDataWithImages); // Agregamos las imagenes O imagen al DATA
                    $woocommerce->post('products', $dataWithImagesAdd);
                }

        }
        
    }


    public function index(){

        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0In0.eyJhdWQiOiJrTDJZRG04dzdTZ3RmT1lKcTdvNWU3WWZscEpqZTNzaCIsImp0aSI6IjNjNWUxYTUzZTAxYWE3ZmFmOTYyNDY4ZWRlZmE5ODkwZmE4MDE0NzZhYzMyOGRlMGMwNzlhMmM5NDI0OTQ5YjhjOTgxN2E1M2Y1NjNhYjY0IiwiaWF0IjoxNjYwMDExMTc3LCJuYmYiOjE2NjAwMTExNzcsImV4cCI6MTY5MTU0NzE3Nywic3ViIjoiIiwic2NvcGVzIjpbXX0.K4ZQA7si16t4M7sT9u6ml_v9lAZ29bmDE75VSEzbzhGSsvuizmJHEXNmhoL_i-SEmofMgsmcasl0uXAIC2BDZqtZ33QQTitNXt3z4S4CDuN54y0U7rqcAyn1EM1qh_vL8cHLCkGd0KVCu-v25aphHDUZD9mKj-QeBSLE4l1hmPMiBgn_lP0vFdsu4nSsTrdgwdu9iOH_intMC-HJAp6EDpTJu8wsrMjcQY4YEDtqpIzPlQSUGoKkfVclDrxlHfoHP7ZESQgDH7eTmAFhyuthejVL1qJv-3JCuUg3YIMWATpD6xlF8kKCdq32rXEpEPcC4PSckZDWbyjlIDV-kWKWxnKDZL3X3SUPEPDUlYPKmC941XZW_sLXQRXCg-MP4aQeNkukSbQNdnB273DKuQGvXKOxgYK81jwjM__kX-ZcXJSqol6aYailLW6JQaK7rMxl6mKCJhr5F6vH4flD1oDcput_BlvGVjDerIlwB6oK9NiFGjVYdNcFqopInRwykNWdkoSMdBjkzxSMdVgUGst1_WI2eM5-bvIhSn2t6Ul_ZUQkqT8GoF9onc90c0hdJnODvMENxYanoRCyORaKZ_a3a1gnGyapZaFNpGo2OTQbph5o9WbS0ZWBKixVA4OTgq4cwpAD1XGxxMGhFnt1KDCl5bMTH0aQLQGvnqHKyje-4Jo";
        $client = new ClientGuzz([
            // Base URI is used with relative requests
            'base_uri' => 'https://developers.syscom.mx/api/v1/',
            // You can set any number of default request options.
            'read_timeout' => 2,
            'timeout'  => 2,            
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
        ]);

        $woocommerce = new Client(
            'http://vito-ecommerce.test',
            'ck_a2cec0ee2b4f6cf41d7a16cb0d439cb2f1add4ca',
            'cs_7e872fa851d450f67e0c5a183488f091b9194b40',
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );

        $categoriaProducto = DB::table('categories')
            ->select('*')
            ->where('nivel', '=', '1')
            ->get();
        $categoriaProducto = json_decode($categoriaProducto, true);

        for ($i=0; $i <count($categoriaProducto); $i++) { 
            $data = [
                'name' => $categoriaProducto[$i]['nombre'],
                'slug' => $categoriaProducto[$i]['id_categoria_syscom']
            ];
            $woocommerce->post('products/categories', $data);
        }

        $categoriaProducto = DB::table('categories')
            ->select('*')
            ->where('nivel', '=', '2')
            ->get();
        $categoriaProducto = json_decode($categoriaProducto, true);

        

        for ($i=0; $i <count($categoriaProducto); $i++) {

            $id_padre = DB::table('categories')
                ->select('*')
                ->where('id', '=', $categoriaProducto[$i]['id_categoria_padre'])
                ->get();
            $id_padre = json_decode($id_padre, true);


            $myArray = $woocommerce->get('products/categories', ['slug' => $id_padre[0]['id_categoria_syscom']]);
            $myOtherArray = json_decode(json_encode($myArray),true);

            $data = [
                'name' => $categoriaProducto[$i]['nombre'],
                'slug' => $categoriaProducto[$i]['id_categoria_syscom'],
                'parent' => $myOtherArray[0]['id']
            ];
            
            $woocommerce->post('products/categories', $data);
        }

        $categoriaProducto = DB::table('categories')
            ->select('*')
            ->where('nivel', '=', '3')
            ->get();
        $categoriaProducto = json_decode($categoriaProducto, true);

        for ($i=0; $i <count($categoriaProducto); $i++) {
            
            $id_padre = DB::table('categories')
                ->select('*')
                ->where('id', '=', $categoriaProducto[$i]['id_categoria_padre'])
                ->get();
            $id_padre = json_decode($id_padre, true);


           
            $myArray = $woocommerce->get('products/categories', ['slug' => $id_padre[0]['id_categoria_syscom']]);
            $myOtherArray = json_decode(json_encode($myArray),true);
            
            $data = [
                'name' => $categoriaProducto[$i]['nombre'],
                'slug' => $categoriaProducto[$i]['id_categoria_syscom'],
                'parent' => (int)$myOtherArray[0]['id']

            ];
            
            $woocommerce->post('products/categories', $data);
        }

        
        // INICIA SUBIDA DE PRODUCTOS VIDEOVIGILANCIA CON IMAGENES BASE DE DATOS
        /*
        for ($i=0; $i < 50 ; $i++) { 
            $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=22&pagina='.$i+1)->getBody()->getContents();
            $data2 = json_decode($response2, true);

            for ($j=0; $j < count($data2['productos']) ; $j++) { 
                $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
                $data3 = json_decode($response3, true);            
                if ($data3['precios'] != null && $data3['categorias'] != null){
                    $categoriaProducto = DB::table('categories')
                        ->select('*')
                        ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
                        ->get();
                    $categoriaProducto = json_decode($categoriaProducto, true);

                    DB::table('productos')
                        ->insert([
                            'id_producto_syscom' => $data3['producto_id'],
                            'titulo' => $data3['titulo'],
                            'modelo' => $data3['modelo'],
                            'stock' => $data3['total_existencia'],
                            'precio' => $data3['precios']['precio_descuento'],
                            'descripcion' => $data3['descripcion'],
                            'img_portada' => $data3['img_portada'],
                            'marca' => $data3['marca'],
                            'id_categoria' => $categoriaProducto[0]['id']
                        ]);

                    if ($data3['imagenes'] != null && count($data3['imagenes']) > 1 ){
                        $idProducto = DB::table('productos')
                        ->select('*')
                        ->where('id_producto_syscom', '=', $data3['producto_id'])
                        ->get();
                       $idProducto = json_decode($idProducto, true);
                       

                      for ($k=1; $k <count($data3['imagenes']) ; $k++) { 
                           DB::table('imagenes')
                           ->insert([
                              'imagen' => $data3['imagenes'][$k]['imagen']
                           ]);

                          $idImg = DB::table('imagenes')
                           ->select('*')
                           ->where('imagen', '=', $data3['imagenes'][$k]['imagen'])
                           ->get();
                          $idImg = json_decode($idImg, true);

                          DB::table('img_productos')
                          ->insert([
                            'id_producto' => $idProducto[0]['id'],
                            'id_imagen' => $idImg[0]['id']
                          ]);
                      }
                    }
                }
            }

        }
        */
        // FINALIZA SUBIDA DE PRODUCTOS VIDEOVIGILANCIA CON IMAGENES


        // EMPIEZA SU8IDA DE PRODUCTOS VIDEOVIGILANCIA A WOOCOMMERCE
        /*
        $productos = DB::table('productos')
            ->select('*')
            ->where('stock', '>=', 80)
            ->get();
        $productos = json_decode($productos, true);


        for ($i=0; $i < count($productos) ; $i++) {
            $idCatBd = DB::table('categories')
                ->select('*')
                ->where('id', '=', $productos[$i]['id_categoria'])
                ->get();
            $idCatBd = json_decode($idCatBd, true);


            $myArray = $woocommerce->get('products/categories', ['slug' => $idCatBd[0]['id_categoria_syscom']]);
            $myOtherArray = json_decode(json_encode($myArray),true);
                
            $data = [
                'name' => $productos[$i]['titulo'],
                'regular_price' => $productos[$i]['precio'],
                'description' => $productos[$i]['descripcion'],
                'short_description' => $productos[$i]['modelo'],
                'slug' => $productos[$i]['id_producto_syscom'],
                'manage_stock' => true,
                'stock_quantity' => $productos[$i]['stock'],

                'categories' => [
                    [
                        'id' => (int) $myOtherArray[0]['id']
                    ]
                ]
            ];
            
                if ($productos[$i]['img_portada'] != null){
                    $idCatBd = DB::table('img_productos')
                    ->select('*')
                    ->where('id_producto', '=', $productos[$i]['id'])
                    ->get();
                $idCatBd = json_decode($idCatBd, true);
            
                // Agregando el data con imagenes
                    $newDataWithImages = array();
                    $newDataWithImages['images'][0]['src'] = $productos[$i]['img_portada'];

                    if (count($idCatBd) > 1){
                        for ($j=0; $j < count($idCatBd); $j++){
                            $imagen = DB::table('imagenes')
                                ->select('*')
                                ->where('id', '=', $idCatBd[$j]['id_imagen'])
                                ->get();
                            $imagen = json_decode($imagen, true);
                     
                            $newDataWithImages['images'][$j+1]['src'] = $imagen[0]['imagen'];
                        }
                    }
                    $dataWithImagesAdd = array_merge($data, $newDataWithImages); // Agregamos las imagenes O imagen al DATA
                    $woocommerce->post('products', $dataWithImagesAdd);
                }

        }
        */

        // TERMINA SU8IDA DE PRODUCTOS VIDEOVIGILANCIA A WOOCOMMERCE
        
        
        


        // for ($i=0; $i < 2 ; $i++) { 

        //     $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=22&pagina='.$i+1)->getBody()->getContents();
        //     $data2 = json_decode($response2, true);

        //     // echo ('-----pagina '.($i+1).'<br>');

        //     for ($j=0; $j < count($data2['productos']) ; $j++) { 

                      
        //         $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
        //         $data3 = json_decode($response3, true);            

        //         if ($data3['precios'] != null && $data3['categorias'] != null){

              
                    
        //             // print_r('producto #'.($j+1).$data3['titulo'].'<br>');

        //             $marcaProducto = DB::table('marcas')
        //                     ->select('*')
        //                     ->where('nombre', '=', $data3['marca'])
        //                     ->get();
        //             $marcaProducto = json_decode($marcaProducto, true);


        //             $categoriaProducto = DB::table('categories')
        //                 ->select('*')
        //                 ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
        //                 ->get();
        //             $categoriaProducto = json_decode($categoriaProducto, true);

                    

        //             DB::table('productos')
        //                 ->insert([
        //                     'id_producto_syscom' => $data3['producto_id'],
        //                     'titulo' => $data3['titulo'],
        //                     'modelo' => $data3['modelo'],
        //                     'stock' => $data3['total_existencia'],
        //                     'precio' => $data3['precios']['precio_descuento'],
        //                     'descripcion' => $data3['descripcion'],
        //                     'img_portada' => $data3['img_portada'],
        //                     'id_marca' => $marcaProducto[0]['id'],
        //                     'id_categoria' => $categoriaProducto[0]['id']
        //                 ]);



        //         }


                
            
                    
        //     }

        // }



        
        // $categoriasN2 = DB::table('categories')
        //                 ->select('*')
        //                 ->where('nivel', '=', '2')
        //                 ->get();
        // $categoriasN2 = json_decode($categoriasN2, true);

        // $categoriasN1 = DB::table('categories')
        // ->select('*')
        // ->where('nivel', '=', '1')
        // ->get();
        // $categoriasN1 = json_decode($categoriasN1, true);
        // SELECT * FROM `categories` WHERE nivel = 2 AND id_categoria_padre = 3
        
        
        // $myArray = $woocommerce->get('products/categories', ['slug' => (string) $categoriasN1[0]['id_categoria_syscom']]);
        // $myOtherArray = json_decode(json_encode($myArray),true);

        // // return $myOtherArray;

        // for ($i=0; $i < count($categoriasN1) ; $i++) { 
            
        //     $categoriasN2 = DB::table('categories')
        //     ->select('*')
        //     ->where('id_categoria_padre', '=', $categoriasN1[$i]['id'])
        //     ->get();
        //     $categoriasN2 = json_decode($categoriasN2, true);
        //     // SELECT * FROM `categories` WHERE nivel = 2 AND id_categoria_padre = 3
        //     // echo ($categoriasN1[0]['nombre']. '<br>');

        //     for ($j=0; $j < count($categoriasN2) ; $j++) {
        //         $myArray = $woocommerce->get('products/categories', ['slug' => $categoriasN1[$i]['id_categoria_syscom']]);
        //         $myOtherArray = json_decode(json_encode($myArray),true);

        //         // echo ($categoriasN2[$j]['nombre'].'<br>');
        //         // echo ($categoriasN2[$j]['nivel'].'<br>');
        //         $data = [
        //             'name' => $categoriasN2[$j]['nombre'],
        //             'slug' => $categoriasN2[$j]['id_categoria_syscom'],
        //             'parent' => (int)$myOtherArray[0]['id']
        //         ];
        //         $woocommerce->post('products/categories', $data);
                    
        //     }
            

        // }



        // $categoriasN2 = DB::table('categories')
        // ->select('*')
        // ->where('nivel', '=', '2')
        // ->get();
        // $categoriasN2 = json_decode($categoriasN2, true);
        // // SELECT * FROM `categories` WHERE nivel = 2 AND id_categoria_padre = 3
        
        
        // // $myArray = $woocommerce->get('products/categories', ['slug' => (string) $categoriasN1[0]['id_categoria_syscom']]);
        // // $myOtherArray = json_decode(json_encode($myArray),true);

        // // // return $myOtherArray;

        // for ($i=0; $i < count($categoriasN2) ; $i++) { 
            
        //     $categoriasN3 = DB::table('categories')
        //     ->select('*')
        //     ->where('id_categoria_padre', '=', $categoriasN2[$i]['id'])
        //     ->get();
        //     $categoriasN3 = json_decode($categoriasN3, true);
        //     // SELECT * FROM `categories` WHERE nivel = 2 AND id_categoria_padre = 3
        //     // echo ($categoriasN1[0]['nombre']. '<br>');

        //     for ($j=0; $j < count($categoriasN3) ; $j++) {
        //         $myArray = $woocommerce->get('products/categories', ['slug' => $categoriasN2[$i]['id_categoria_syscom']]);
        //         $myOtherArray = json_decode(json_encode($myArray),true);

        //         $data = [
        //             'name' => $categoriasN3[$j]['nombre'],
        //             'slug' => $categoriasN3[$j]['id_categoria_syscom'],
        //             'parent' => (int)$myOtherArray[0]['id']
        //         ];
        //         $woocommerce->post('products/categories', $data);
                    
        //     }
            

        // }






        // Categorias nivel 1 en wordpress
        // for ($i=0; $i < count($categorias) ; $i++) { 
        //       $myArray = $woocommerce->get('products/categories', ['slug' => $categorias[$i]['id_categoria_syscom']]);
        //       $myOtherArray = json_decode(json_encode($myArray),true);

        //     //   print_r($myOtherArray);
            
        //     //   $data = [
        //     //     'name' => $categorias[$i]['nombre'],
        //     //     'slug' => $categorias[$i]['id_categoria_syscom'],
        //     //     'parent' => (int)$myOtherArray[$i]['id']
        //     // ];
        //     // $woocommerce->post('products/categories', $data);
        // }

        // $myArray = $woocommerce->get('products/categories', ['slug' => 'example']);
        // $myOtherArray = json_decode(json_encode($myArray),true);
        

        // Categorias nivel 2

        // $categorias = DB::table('categories')
        //                 ->select('*')
        //                 ->where('nivel', '=', '2', 'AND', 'id_categoria_padre', '=', '2')
        //                 ->get();
        // $categorias = json_decode($categorias, true);

        // // return $categorias;

                
        // for ($i=0; $i < count($categorias) ; $i++) { 
        //     $data = [
        //         'name' => $categorias[$i]['nombre'],
        //         'slug' => $categorias[$i]['id_categoria_syscom'],
        //         'parent' => 444
        //     ];
        //     print_r($woocommerce->post('products/categories', $data));
        // }

        
        // $data = [
        //     'name' => 'Clothing',
        //     'image' => [
        //         'src' => 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_front.jpg'
        //     ]
        //     'slug' => 
        // ];

        // print_r($woocommerce->post('products/categories', $data));





        

        // AGREGANDO PRODUCTOS DE VIDEOVIGILANCIA
        // $response2 = $client->request('GET', 'productos/159031')->getBody()->getContents();
        // $data2 = json_decode($response2, true);


        // $productos = DB::table('productos')
        //                 ->select('*')
        //                 ->where('stock', '>=', 80)
        //                 ->get();
        // $productos = json_decode($productos, true);

        // return count($productos);

        // for ($i=0; $i <count($productos) ; $i++) { 

        //     if ($productos[$i]['img_portada'] != null){
        //         $stock = (int)$productos[$i]['stock'];
        //         $data = [
        //             'name' => $productos[$i]['titulo'],
        //             'regular_price' => $productos[$i]['precio'],
        //             'description' => $productos[$i]['descripcion'],
        //             'short_description' => $productos[$i]['modelo'],
        //             'manage_stock' => true,
        //             'stock_quantity' => $stock,
        //             'images' => [
        //                 [
        //                     'src' => $productos[$i]['img_portada']
        //                 ],
        //             ]
        //         ];
                
        //         $woocommerce->post('products', $data);

        //     }

        // }

        // print_r($productos);
        
        

        // for ($i=0; $i < 50 ; $i++) { 

        //     $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=22&pagina='.$i+1)->getBody()->getContents();
        //     $data2 = json_decode($response2, true);

        //     // echo ('-----pagina '.($i+1).'<br>');

        //     for ($j=0; $j < count($data2['productos']) ; $j++) { 

                
        //         $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
        //         $data3 = json_decode($response3, true);            
        //         // print_r('producto #'.($j+1).$data3['titulo'].'<br>');

        //             $marcaProducto = DB::table('marcas')
        //                     ->select('*')
        //                     ->where('nombre', '=', $data3['marca'])
        //                     ->get();
        //             $marcaProducto = json_decode($marcaProducto, true);


        //             $categoriaProducto = DB::table('categories')
        //                 ->select('*')
        //                 ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
        //                 ->get();
        //             $categoriaProducto = json_decode($categoriaProducto, true);

        //         if ($data3['precios'] != null){

        //                 DB::table('productos')
        //                     ->insert([
        //                         'id_producto_syscom' => $data3['producto_id'],
        //                         'titulo' => $data3['titulo'],
        //                         'modelo' => $data3['modelo'],
        //                         'stock' => $data3['total_existencia'],
        //                         'precio' => $data3['precios']['precio_descuento'],
        //                         'descripcion' => $data3['descripcion'],
        //                         'img_portada' => $data3['img_portada'],
        //                         'id_marca' => $marcaProducto[0]['id'],
        //                         'id_categoria' => $categoriaProducto[0]['id']
        //                     ]);



        //         }

                    
        //     }

        // }

        
        // for ($i=3; $i < 6 ; $i++) { 

        //     $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=22&pagina='.$i+1)->getBody()->getContents();
        //     $data2 = json_decode($response2, true);

        //     echo ('-----pagina '.$i+1);

        //     for ($j=0; $j < 60 ; $j++) { 
        //         $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
        //         $data3 = json_decode($response3, true);            
        //         print_r('producto #'.($j+1).$data3['titulo'].'<br>');
    
        //     }

            
            
        // }

        

        

        

        
        // for ($i=3; $i < 6 ; $i++) { 

        //     $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=22&pagina='.$i+1)->getBody()->getContents();
        //     $data2 = json_decode($response2, true);

        //     echo ('-----pagina '.$i+1);

        //     for ($j=0; $j < 60 ; $j++) { 
        //         $response3 = $client->request('GET', 'productos/'.$data2['productos'][$j]['producto_id'])->getBody()->getContents();
        //         $data3 = json_decode($response3, true);            
        //         print_r('producto #'.($j+1).$data3['titulo'].'<br>');
    
        //     }

            
            
        // }

        

        

        // https://developers.syscom.mx/api/v1/productos?stock=true&orden=topseller&categoria=38

        // $categoriasDeBD = DB::table('categories')
        //     ->select('*')
        //     ->where('nivel', '=', '1')
        //     ->get();
        // $categoriasDeBD = json_decode($categoriasDeBD, true);
        // $j = 0;
        // $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=38&pagina='.$j+1)->getBody()->getContents();
        //         $data2 = json_decode($response2, true);

        // // print_r($data2);
        
        // $k = 0;
        // $response3 = $client->request('GET', 'productos/'.$data2['productos'][$k]['producto_id'])->getBody()->getContents();
        // $data3 = json_decode($response3, true);


        // if ($data3['precios']['precio_descuento'] == null){
        //     echo 'es nulo';
        // }

        // $precioDescuento = $da
        // print_r($data3['precios']);

        // $precioConDescuento = $data3['precios']['precio_descuento'];

        // echo $data3['precios'];

        // $precioConDescuento = $this->calcularPrecio($precioConDescuento);
        // $cantProductos = 0;
        // $cantProductosNoAceptados = 0;
        // for ($i=0; $i < 1; $i++) { 
            
        //     $response = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=38')->getBody()->getContents();
        //     $data = json_decode($response, true);
            
        //     for ($j=0; $j < $data['paginas']; $j++) { 
        //         $response2 = $client->request('GET', 'productos?stock=true&orden=topseller&categoria=38&pagina='.$j+1)->getBody()->getContents();
        //         $data2 = json_decode($response2, true);

        //         for ($k=0; $k < count($data2['productos']); $k++) { 
        //             $response3 = $client->request('GET', 'productos/'.$data2['productos'][$k]['producto_id'])->getBody()->getContents();
        //             $data3 = json_decode($response3, true);
    
        //             // $precioConDescuento = $data3['precios']['precio_descuento'];

                    
        //             $marcaProducto = DB::table('marcas')
        //                     ->select('*')
        //                     ->where('nombre', '=', $data3['marca'])
        //                     ->get();
        //             $marcaProducto = json_decode($marcaProducto, true);


        //             $categoriaProducto = DB::table('categories')
        //                 ->select('*')
        //                 ->where('id_categoria_syscom', '=', $data3['categorias'][0]['id'])
        //                 ->get();
        //             $categoriaProducto = json_decode($categoriaProducto, true);

                    

        //             //    DB::table('productos')
        //             //         ->insert([
        //             //             'id_producto_syscom' => $data3['producto_id'],
        //             //             'titulo' => $data3['titulo'],
        //             //             'modelo' => $data3['modelo'],
        //             //             'stock' => $data3['total_existencia'],
        //             //             'precio' => $data3['precios']['precio_descuento'],
        //             //             'descripcion' => $data3['descripcion'],
        //             //             'img_portada' => $data3['img_portada'],
        //             //             'id_marca' => $marcaProducto[0]['id'],
        //             //             'id_categoria' => $categoriaProducto[0]['id']
        //             //         ]);  

        //             print_r('Se agrego producto <br>');
        //             $cantProductos = $cantProductos + 1;

                    
                                        
                        


        //         }

              
                

                
                
        //     }
           
            

        // }


        
    }
    
}
 // $response = $client->request('GET', 'marcas/'.$data[$i]['id'])->getBody()->getContents();
            // $data = json_decode($response, true);





            // DB::table('marcas')
            // ->insert([
            //     'id_marca_syscom' => $data[$i]['id'],
            //     'nombre' => $data[$i]['nombre'],
            //     'logo' => $data2['logo']
            // ]);