function verCrearDirectorio()
{
    new Ajax.Request('/admin.php/jfilebrowser/templateCrearDirectorio', {
        method: 'post',
        onSuccess: function (response){
            $('container_tinyPlugin').update(response.responseText);
        }
    });
}

function crearDirectorio(form)
{
    form.request({
        onSuccess: function (response){
            var json = response.responseText.evalJSON();
            if(json.response == 'OK'){
                $('container_tinyPlugin').update(json.content);
            }else{
                $('mensaje_error2').insert(json.content);
                $('mensaje_error2').show();
            }

        }
    });
}

function borrar(form)
{
    form.request({
        onSuccess: function (response){
            var json = response.responseText.evalJSON();
            if(json.response == 'OK'){
                $('container_tinyPlugin').update(json.content);
            }else{
                $('mensaje_error2').insert(json.content);
                $('mensaje_error2').show();
            }

        }
    });
}

function verCategoria(idcat)
{
    var view = (arguments[1] === undefined ? 'thumbnails' : arguments[1]);
    new Ajax.Request('/admin.php/jfilebrowser/verCategoria', {
        method: 'post',
        parameters: 'id=' + idcat + '&view=' + view,
        onSuccess: function (response){
            $('container_tinyPlugin').update(response.responseText);
        }
    });
}

function verSubirArchivo(idcat)
{
    new Ajax.Request('/admin.php/jfilebrowser/templateSubirArchivo', {
        method: 'post',
        parameters: 'directorio=' + idcat,
        onSuccess: function (response){
            $('container_tinyPlugin').update(response.responseText);
        }
    });
}

function buscar()
{
    $('buscar_fm').request({
        onSuccess: function (response){
            $('container_tinyPlugin').update(response.responseText);
        }
    });
}

function salvarImagen()
{
    $('form1').request({
        onSuccess: function (response){
            $('container_tinyPlugin').update(response.responseText);
        }
    });
}

function verThumb(idcat,b)
{
    new Ajax.Request('/admin.php/jfilebrowser/verThumb', {
        method: 'post',
        parameters: 'set_c=1&id=' + idcat + '&busqueda=' + b,
        onSuccess: function (response){
            $('container_tinyPlugin').update(response.responseText);
        }
    });
}

function verLista(idcat,b)
{
    new Ajax.Request('/admin.php/jfilebrowser/verLista', {
        method: 'post',
        parameters: 'set_c=2&id=' + idcat + '&busqueda=' + b,
        onSuccess: function (response){
            $('container_tinyPlugin').update(response.responseText);
        }
    });
}

function view(directorio, id)
{
    new Ajax.Request('/admin.php/jfilebrowser/templateView', {
        method: 'post',
        parameters: 'name=' + id+'&directorio=' + directorio,
        onSuccess: function (response){
            var json = response.responseText.evalJSON();
            $('container_tinyPlugin').update(json.content);
        }
    });
}

function init()
{
    var url = (arguments[0] === undefined ? '/admin.php/jfilebrowser/index' : arguments[0]);
    new Ajax.Request(url, {
        method: 'post',
        onSuccess: function (response)
        {
            $('container_tinyPlugin').update(response.responseText);
        }
    });
}