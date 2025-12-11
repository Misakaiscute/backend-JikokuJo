<?php

namespace App\Http\Controllers;

use DirectoryIterator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SearchController extends Controller
{
    private array $routeTypes = [
        0=>'Villamos',1=>'Metró',2=>'Vonat',3=>'Busz',4=>'Komp',5=>'Sikló',6=>'Libegő',
        7=>'Fogaskerekű',11=>'Trolibusz',12=>'Egysínű vasút',
        100=>'Vasúti szolgáltatás',101=>'Nagysebességű vonat',102=>'Távolsági vonat',
        103=>'Interregionális vonat',104=>'Autószállító vonat',105=>'Hálókocsi',
        106=>'Regionális vonat',107=>'Turista vasút',108=>'Vasúti ingajárat',
        109=>'Elővárosi vasút',110=>'Pótló vasút',111=>'Különjárat vasút',
        112=>'Tehergépjármű szállító vasút',113=>'Minden vasúti szolgáltatás',114=>'Országon átívelő vonat',
        115=>'Járműszállító vasút',116=>'Fogaskerekű vasút',117=>'Kiegészítő vasúti szolgáltatás',
        200=>'Távolsági busz',201=>'Nemzetközi távolsági busz',202=>'Országos távolsági busz',
        203=>'Ingajárat busz',204=>'Regionális távolsági busz',205=>'Különjárat távolsági busz',
        206=>'Városnéző busz',207=>'Turista busz',208=>'Ingázó busz',
        209=>'Minden távolsági busz',300=>'Elővárosi vasút',400=>'Városi vasút',
        401=>'Metró',402=>'Földalatti',403=>'Városi vasút',
        404=>'Minden városi vasút',500=>'Metró',600=>'Földalatti',
        700=>'Busz',701=>'Regionális busz',702=>'Gyorsbusz',
        703=>'Megállóhelyes busz',704=>'Helyi busz',705=>'Éjszakai busz',
        706=>'Postabusz',707=>'Mozgáskorlátozottak busza',708=>'Mozgássérült busz',
        709=>'Regisztrált mozgássérültek busza',710=>'Városnéző busz',711=>'Ingajárat busz',
        712=>'Iskolabusz',713=>'Iskola és közösségi busz',714=>'Vasúti pótló busz',
        715=>'Igény szerinti busz',716=>'Minden busz',800=>'Trolibusz',
        900=>'Villamos',901=>'Városi villamos',902=>'Helyi villamos',
        903=>'Regionális villamos',904=>'Városnéző villamos',905=>'Ingajárat villamos',
        906=>'Minden villamos',1000=>'Vízi közlekedés',1001=>'Nemzetközi autókomp',
        1002=>'Országos autókomp',1003=>'Regionális autókomp',1004=>'Helyi autókomp',
        1005=>'Nemzetközi személykomp',1006=>'Országos személykomp',
        1007=>'Regionális személykomp',1008=>'Helyi személykomp',
        1009=>'Postahajó',1010=>'Vonatkompjárat',1011=>'Úti kompjárat',
        1012=>'Repülőtéri kompjárat',1013=>'Autós gyorskomp',
        1014=>'Személyes gyorskomp',1015=>'Városnéző hajó',
        1016=>'Iskolahajó',1017=>'Kötélvontatású hajó',1018=>'Folyami busz',
        1019=>'Menetrend szerinti komp',1020=>'Ingajárat komp',1021=>'Minden vízi közlekedés',
        1100=>'Légi közlekedés',1101=>'Nemzetközi légi járat',1102=>'Belföldi légi járat',
        1103=>'Interkontinentális légi járat',1104=>'Belföldi menetrend szerinti járat',
        1105=>'Ingajárat légi járat',1106=>'Interkontinentális charterjárat',
        1107=>'Nemzetközi charterjárat',1108=>'Oda-vissza charterjárat',
        1109=>'Városnéző légi járat',1110=>'Helikopter járat',1111=>'Belföldi charterjárat',
        1112=>'Schengeni légi járat',1113=>'Léghajó',1114=>'Minden légi járat',
        1200=>'Komp',1300=>'Felvonó',1301=>'Felvonó',
        1302=>'Drótkötélpálya',1303=>'Lift',1304=>'Széklift',
        1305=>'Vontatott lift',1306=>'Kis felvonó',1307=>'Minden felvonó',
        1400=>'Sikló',1401=>'Sikló',1402=>'Minden sikló',
        1500=>'Taxi',1501=>'Közösségi taxi',1502=>'Vízi taxi',
        1503=>'Vasúti taxi',1504=>'Kerékpár taxi',1505=>'Engedéllyel rendelkező taxi',
        1506=>'Bérelt jármű',1507=>'Minden taxi',1600=>'Saját vezetésű',
        1601=>'Bérelt autó',1602=>'Bérelt furgon',1603=>'Bérelt motor',1604=>'Bérelt kerékpár',
        1605=>'Minden saját vezetésű jármű'];
        public function queryables()
        {
            $stops = DB::table('stops')
                ->select('name', DB::raw('GROUP_CONCAT(id) as ids'))
                ->groupBy('name')
                ->get();
        
            $routes = DB::table('routes')
                ->select('short_name', 'id', 'type')
                ->get()
                ->map(function ($route) {
                    return [
                        'route_id'         => $route->id,
                        'route_short_name' => $route->short_name,
                        'type'             => $this->routeTypes[$route->type] ?? 'Unknown'
                    ];
                });
        
            if ($stops->isEmpty() && $routes->isEmpty()) {
                return response()->json([
                    'data'   => [
                        'stops'  => [],
                        'routes' => []
                    ],
                    'errors' => [['error' => 'No data available']]
                ], 404);
            }
        
            return response()->json([
                'data'   => [
                    'stops'  => $stops,
                    'routes' => $routes
                ],
                'errors' => []
            ], 200);
        }

    
}
