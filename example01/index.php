<?php
/**
 * 01 - The first example of use of Armor
 * @author 14mPr0gr4mm3r
 * @license MIT
 */

require_once "./vendor/autoload.php";

use Armor\Handle\Request;
use Armor\Handle\Response;

$app = new Armor\Application();
$templ = new ArmorUI\TemplateManager("./", ["header", "index"]);

if ((function_exists('apc_fetch') && !($db = apc_fetch('db_cache')))
    || (!function_exists('apc_fetch'))) 
{
    $db = array();

    for($i = 0; $i < 1000; $i++) {
        $name = 'Person';
        $description = null;
        $birthday = implode('/', [
            random_int(1, 31),
            random_int(1, 12),
            random_int(1900, 2100)
        ]);

        $db[$i] = array('name' => 'Person', 'desc' => null, 'birthday' => '12/10/1979');
    }

    if(function_exists('apc_fetch')) {
        apc_add('db_cache', $db, 3600);
    }
}

class User {
    private $id, $name, $desc, $birthday;

    public function __construct(int $id, string $name, $desc, $birthday)
    {
        $this->id = $id;
        $this->name = $name;
        $this->desc = $desc;
        $this->birthday = $birthday;
    }

    public static function loadFromID(int $id) {
        global $db;
        return array_key_exists($id, $db) ? new User($id, ...array_values($db[$id])) : exit('User not found');
    }

    public function __toString()
    {
        return "User({ id: {$this->id}, name: {$this->name}, desc: {$this->desc}, birthday: {$this->birthday} })";
    }
}

$my_handlers = array(
    function(Request $req, Response $res) {
        $template = Response::loadContentFrom("pages.json", Response::JSON_PARSE);
        $keys = explode('/', substr($req->path->absolute, 1));
    
        $content = $template;
    
        foreach ($keys as $key) {
            $content = $content[$key];
        }
    
        return $res->end($content['content']);
    },
    function(Request $req, Response $res) use($templ) {
        $templ->getTemplate("index")->sendAsResponse($res);
    
        return $res->end();
    } 
);

$app->use('MyHandlers', $my_handlers);

$app->get('/users/$(user:toint:toparse)', function(Request $req, Response $res) {
    $res->append((string)$req->path['user']);
    return $res->end();
})->setParser(function($id) { return User::loadFromID($id); });

$app->get('/templates-examples/$(examplename)', function(Request $req, Response $res, \Armor\Application $app) {
    switch($req->path['examplename']) {
        case 'templates-json':
            return call_user_func($app['MyHandlers'][0], $req, $res);
        case 'templates-framework':
            return call_user_func($app['MyHandlers'][1], $req, $res);
        default:
            break;
    }

    return $res->end("", 404);
});

$app->run();