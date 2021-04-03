info = getPathInfo(app_folder);
hideYourLink();

if (info.board !== undefined && info.board != null) {
    input_board = document.getElementById('board');
    input_board.value = info.board;
    searchBoard();
}

// Board info functions

// Board content functions

function getBoardContent() {
    textarea_content = document.getElementById('content');
    board_content = textarea_content.value;
    return (board_content == '' || board_content === undefined) ? '' : board_content;
}

function setContent(value)
{
    textarea_content = document.getElementById('content');
    textarea_content.value = value;
}

function validateBoardContent(content) {
    if (content === undefined || content == '') throw ("Contenido no válido");
}

// Board content functions -- END

// Board created functions

function hideCreatedDiv() {
    div = document.getElementById('div_created');
    div.style.display = 'none';
}

function setCreated(value) {
    if (value !== undefined) {
        var date = new Date(value);
        p = document.getElementById('p_created');

        p.innerHTML = "Nota creada el " + date.getUTCFullYear() + "/" + (date.getUTCMonth() + 1) + "/" + date.getUTCDate() + " a las " + date.getUTCHours() + ":" + date.getUTCMinutes();
        showCreatedDiv();
        return;
    }
    hideCreatedDiv();
}

function showCreatedDiv() {
    div = document.getElementById('div_created');
    div.style.display = 'block';
}
// Board created functions -- END

// Board destroy functions
function getBoardDestroy() {
    checkbox_destroy = document.getElementById('destroy');
    return checkbox_destroy !== undefined && checkbox_destroy.checked ? 1 : 0;
}

// Board destroy functions -- END

// Board link functions

function hideYourLink() {
    div = document.getElementById('div_link');
    div.style.display = 'none';
}

function setBoardLink(url) {
    a = document.getElementById('link');
    a.href = url;
    a.text = url;
}

function showYourLink() {
    div = document.getElementById('div_link');
    div.style.display = 'block';
}

// Board link functions -- END

// Board name functions

function getBoardName() {
    input_board = document.getElementById('board');
    board_name = input_board.value;
    return (board_name == '' || board_name === undefined) ? '' : board_name.replace(/\s/g, '');
}

function validateBoardName(name) {
    if (name === undefined || name == '') throw ("Nombre no válido");
}

// Board name functions -- END

// Others

function copyToClipboard(id_elemento) {

    // Crea un campo de texto "oculto"
    var aux = document.createElement("input");

    // Asigna el contenido del elemento especificado al valor del campo
    aux.setAttribute("value", document.getElementById(id_elemento).innerHTML);

    // Añade el campo a la página
    document.body.appendChild(aux);

    // Selecciona el contenido del campo
    aux.select();

    // Copia el texto seleccionado
    document.execCommand("copy");

    // Elimina el campo de la página
    document.body.removeChild(aux);

}

function resetBoardFields() {
    input_board = document.getElementById('board');
    input_board.value = '';

    textarea_content = document.getElementById('content');
    textarea_content.value = '';

    checkbox_destroy = document.getElementById('destroy');
    checkbox_destroy.checked = false;

    hideYourLink();
    hideCreatedDiv();
}

// Others -- END


// APP functions

/**
 * Función que hace una llamada a la API de manera asíncrona hasta que se resuelve.
 * Funciona, de la manera que es una promesa resuelta, una vez resuelta (return data.boards[0]) o (Exception)
 * Ese es el valor que usando await en el fetch se asigna a response.
 * Después, response es el resultado de la función.
 * En caso de Exception, pasa al bloque catch y devolvemos el json con la key error y la excepción
 * @param  {String} name board a buscar
 * @return {[type]}      json with board object or error
 */
async function getBoard(name = '')
{
    try{

        validateBoardName(name);

        let response = await fetch('api/boards/' + name)
        .then(response => response.json())
        .then(function (data) {
            console.log("API response")
            if (typeof data.boards !== "undefined" && data.boards.length == 1) {
                console.log("Return board to response");
                console.info(data.boards[0]);
                return data.boards[0];
            }
            throw "BOARD NOT FOUND";
        });
        console.log("Return promise?");
        return response;
    }catch(error){
        console.log(error);
        return null;
    }
}

function getPathInfo(app_folder) {

    parts_lenght = app_folder == '' ? 2 : 3;
    url = window.location.pathname;

    var info = {};
    info.folder = url.substring(0, url.lastIndexOf('/'));
    parts = url.split('/');

    if (app_folder != '' && parts[1] != app_folder) {
        throw ("INVALID_APP_FOLDER_CONFIG");
    }

    info.board = parts.length == parts_lenght && '' != parts[parts_lenght - 1] ? parts[parts_lenght - 1] : null;
    return info;
}

function gotoBoard(board) {
    try {
        setContent(board.content);
        setCreated(board.created);
        history.pushState(null, "", info.folder + "/" + board.name);
        setBoardLink(window.location.href);
        showYourLink();
        if (board.destroyed) {
            alert("NOTA AUTODESTRUIDA");
            hideYourLink();
        }
    } catch (error) {
        alert(error);
        return;
    }
}

function saveBoard() {
    try {
        board_name = getBoardName();
        validateBoardName(board_name);
        board_content = getBoardContent();
        validateBoardContent(board_content);
        board_destroy = getBoardDestroy();
        var url = 'api/boards/';
        var data = {
            name: board_name,
            content: board_content,
            destroy: board_destroy
        };

        postData(url, data).then(result => {

            // console.info("RESULT POST", result);
            if (result.status == 200) {
                alert("Nota guardada correctamente");
                gotoBoard(result.board);
                return;
            }

            if (result.status == 202) {
                alert('Nombre ya existente, por favor elige otro nombre');
                return;
            }

            alert("Error guardando nota. Intenta de nuevo.\nError: " + result.error);

        });

    } catch (error) {
        alert(error);
        return;
    }
}

async function searchBoard() {
    board_name = getBoardName();
    board = await getBoard(board_name);
    if (board === undefined || board === null) {
        alert("No hay ninguna nota con el nombre: \n" + board_name);
        history.pushState(null, "", info.folder + "/");
        resetBoardFields();
        return;
    }
    gotoBoard(board);
}

// APP functions -- END

// Fetch functions

async function postData(url = '', data = {}) {
    // Default options marked as (*)
    const response = await fetch(url, {
        method: 'POST', // *GET, POST, PUT, DELETE, etc.
        mode: 'cors', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'Content-Type': 'application/json'
            // 'Content-Type': 'application/x-www-form-urlencoded',
        },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: JSON.stringify(data) // body data type must match "Content-Type" header
    });
    return response.json(); // parses JSON response into native JavaScript objects
}

// Fetch functions -- END