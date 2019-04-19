<?php
$usuario_recibido   = $_SERVER['PHP_AUTH_USER'];
$pass_recibido      = $_SERVER['PHP_AUTH_PW'];
$inputJSON          = file_get_contents('php://input');
$input              = json_decode($inputJSON, TRUE);
$respuesta          = "";
$respuesta_texto    = "";
$respuesta_cards[]  = array();
$respuesta_images[] = array();
header('Content-Type: application/json;charset=utf-8');

/*
_______  _______  _______ _________ ______  _________ _______
(  ____ )(  ____ \(  ____ \\__   __/(  ___ \ \__   __/(  ____ )
| (    )|| (    \/| (    \/   ) (   | (   ) )   ) (   | (    )|
| (____)|| (__    | |         | |   | (__/ /    | |   | (____)|
|     __)|  __)   | |         | |   |  __ (     | |   |     __)
| (\ (   | (      | |         | |   | (  \ \    | |   | (\ (
| ) \ \__| (____/\| (____/\___) (___| )___) )___) (___| ) \ \__
|/   \__/(_______/(_______/\_______/|/ \___/ \_______/|/   \__/
*/
//comprueba credenciales
function credenciales($usuario, $pass)
{
    global $usuario_recibido;
    global $pass_recibido;
    if (($usuario != $usuario_recibido) OR ($pass != $pass_recibido)) {
        echo "Acceso no autorizado";
        die();
    }
}

//graba en archivos debugs
function debug()
{
    global $input;
    $json_string = json_encode($input, JSON_PRETTY_PRINT);
    file_put_contents('json.js', $json_string);
    file_put_contents('credenciales_recibidas.js', "Usuario: " . $_SERVER['PHP_AUTH_USER'] . " Pass: " . $_SERVER['PHP_AUTH_PW']);
    file_put_contents('array.php', "<?php " . print_r($input, TRUE));
}

//graba en archivos debugs variable especifica
function debug_variable($variable)
{
    file_put_contents('debug_variable.js', $variable);
}

//comprueba intent recibido
function intent_recibido($nombre)
{
    global $input;
    if ($input["queryResult"]["intent"]["displayName"] == $nombre) {
        return true;
    } else {
        return false;
    }
}

//recibirá las variables y las almacenará en un array.
function recibir_variables($nombre)
{
    global $input;
    if ($input["queryResult"]["intent"]["displayName"] == $nombre) {
        return true;
    } else {
        return false;
    }
}

//devuelve el response id
function responseId()
{
    global $input;
    return $input["responseId"];
}


//devuelve true si estàn todos los datos requeridos
function requeridosPresentes()
{
    global $input;
    return $input["queryResult"]["allRequiredParamsPresent"];
}

//devuelve el origen si es distinto al chat de dialogflow
function origen()
{
    global $input;
    if (isset($input["originalDetectIntentRequest"]["payload"]["source"])) {
        return strtoupper($input["originalDetectIntentRequest"]["payload"]["source"]); //lo transformamos todo en mayúscula
    } else {
        return "INDETERMINADO";
    }
}

//obtiene texto en caso de existir...
function obtener_texto()
{
    global $input;
    if (isset($input["queryResult"]["queryText"])) {
        return $input["queryResult"]["queryText"];
    } else {
        return "";
    }
}

//obtiene variables...
function obtener_variables()
{
    global $input;
    if (isset($input["queryResult"]["parameters"])) {
        return $input["queryResult"]["parameters"];
    } else {
        return "";
    }
}



//obtiene imagenes adjuntas en caso de existir...
function obtener_imagen()
{
    global $input;
    if (isset($input["originalDetectIntentRequest"]["payload"]["data"]["message"]["attachments"][0]["payload"]["url"])) {
        return $input["originalDetectIntentRequest"]["payload"]["data"]["message"]["attachments"][0]["payload"]["url"];
    } else {
        return "";
    }
}


//obtiene imagenes adjuntas en caso de existir...
function obtener_posicion()
{
    global $input;
    if (isset($input["originalDetectIntentRequest"]["payload"]["data"]["postback"]["data"]["lat"])) {
        $posicion['lat'] = $input["originalDetectIntentRequest"]["payload"]["data"]["postback"]["data"]["lat"];
        $posicion['long'] = $input["originalDetectIntentRequest"]["payload"]["data"]["postback"]["data"]["long"];
        return $posicion;
    } else {
        $posicion['lat'] = "SINPOSICION";
        $posicion['long'] = "SINPOSICION";
        return $posicion;
    }
}

// devuelve si vienen adjuntos en el mensaje
function hay_adjunto()
{
    global $input;
    if (isset($input["originalDetectIntentRequest"]["payload"]["data"]["message"]["attachments"])) {
        return TRUE;
    } else {
        return FALSE;
    }
}

// devuelve el tipo de adjunto
function tipo_adjunto()
{
    global $input;
    if (isset($input["originalDetectIntentRequest"]["payload"]["data"]["message"]["attachments"][0]["type"])) {
        return $input["originalDetectIntentRequest"]["payload"]["data"]["message"]["attachments"][0]["type"];
    }
}

/*
_______  _                _________ _______  _______
(  ____ \( (    /||\     /|\__   __/(  ___  )(  ____ )
| (    \/|  \  ( || )   ( |   ) (   | (   ) || (    )|
| (__    |   \ | || |   | |   | |   | (___) || (____)|
|  __)   | (\ \) |( (   ) )   | |   |  ___  ||     __)
| (      | | \   | \ \_/ /    | |   | (   ) || (\ (
| (____/\| )  \  |  \   /  ___) (___| )   ( || ) \ \__
(_______/|/    )_)   \_/   \_______/|/     \||/   \__/
*/

//a esta función se le debe pasar un array que contenga las urls de las imágenes y la plataforma a donde debe enviarse el mensaje
function enviar_imagenes($imagenes, $plataforma)
{
    echo '{"fulfillmentMessages":[';
    foreach ($imagenes as $imagen) {
        echo '{"image":{"imageUri":"' . $imagen . '"},"platform":"' . $plataforma . '"},';
    }
    echo '{"payload":{}}]}' . PHP_EOL;
}

//a esta función se le debe pasar un array que contenga las tarjetas, con los índices titulo subtitulo y url, luego bajo el indice "botones" cargamos otro array con lo que debe indicar cada botón
function enviar_tarjetas($tarjetas, $plataforma)
{
    echo '{"fulfillmentMessages": [';
    foreach ($tarjetas as $tarjeta) {
        echo '
      {
        "card": {
          "title": "' . $tarjeta['titulo'] . '",
          "subtitle": "' . $tarjeta['subtitulo'] . '",
          "imageUri": "' . $tarjeta['url'] . '",
          "buttons": [';
        $str = "";
        foreach ($tarjeta['botones'] as $boton) {
            $str = $str . '{"text":"' . $boton . '"},';
        }
        echo rtrim($str, ',');
        echo ']
        },
        "platform": "' . $plataforma . '"
      },';
    }
    echo '{"payload":{}}]}' . PHP_EOL;
}


//tan simple como pasarle el texto al devolver.
function enviar_texto($texto)
{
    echo '{"fulfillmentText": "' . $texto . '",
    "fulfillmentMessages": [
      {
        "text": {
          "text": [
            "' . $texto . '"
          ]
        }
      }
    ]}' . PHP_EOL;
}

function enviar_respuestas_rapidas($respuestas, $plataforma)
{
    echo '  {
      "fulfillmentMessages": [
        {
          "quickReplies": {
            "title": "' . $respuestas['titulo'] . '",
            "quickReplies": [ ';
    $str = "";
    foreach ($respuestas['botones'] as $boton) {
        $str = $str . '"' . $boton . '",';
    }
    echo rtrim($str, ',');
    echo '
            ]
          },
          "platform": "' . $plataforma . '"
        }
      ] }' . PHP_EOL;

  }
