info = getPathInfo(app_folder);
hideYourLink();

if (info.board !== undefined && info.board != null) {
    input_board = document.getElementById('board');
    input_board.value = info.board;
    gotoBoard();
}

// Functions

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

function getBoardContent() {
    textarea_content = document.getElementById('content');
    board_content = textarea_content.value;
    return (board_content == '' || board_content === undefined) ? '' : board_content;
}

function getBoardName() {
    input_board = document.getElementById('board');
    board_name = input_board.value;
    return (board_name == '' || board_name === undefined) ? '' : board_name.replace(/\s/g, '');
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

function gotoBoard() {

    try {

        board_name = getBoardName();
        validateBoardName(board_name);
        fetch('api/boards/' + board_name)
            .then(response => response.json())
            .then(
                function (data) {

                    if (typeof data.boards !== "undefined" && data.boards.length == 1) {

                        console.log(data.boards[0]);

                        textarea_content = document.getElementById('content');
                        textarea_content.value = data.boards[0].content;

                        setCreated(data.boards[0].created);

                        text_url = document.getElementById('url');

                        history.pushState(null, "", info.folder + "/" + board_name);
                        setBoardLink(window.location.href);
                        showYourLink();

                    } else {

                        alert("No hay ninguna nota con el nombre: \n" + board_name);
                        history.pushState(null, "", info.folder + "/");
                        resetBoardFields();

                    }

                }
            );
    } catch (error) {
        alert(error);
        return;
    }

}

function resetBoardFields() {
    input_board = document.getElementById('board');
    input_board.value = '';

    textarea_content = document.getElementById('content');
    textarea_content.value = '';

    hideYourLink();
    hideCreatedDiv();
}

function saveBoard() {

    try {

        board_name = getBoardName();
        validateBoardName(board_name);
        board_content = getBoardContent();
        validateBoardContent(board_content);
        var url = 'api/boards/';
        var data = {
            name: board_name,
            content: board_content
        };

        postData(url, data).then(result => {

            // console.info("RESULT POST", result);
            if (result.status == 200) {
                alert("Nota guardada correctamente");
                gotoBoard();
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

function validateBoardName(name) {
    if (name === undefined || name == '') throw ("Nombre no válido");
}

function validateBoardContent(content) {
    if (content === undefined || content == '') throw ("Contenido no válido");
}

// Show board link functions
function hideYourLink() {
    div = document.getElementById('div_link');
    div.style.display = 'none';
}

function showYourLink() {
    div = document.getElementById('div_link');
    div.style.display = 'block';
}

function hideCreatedDiv() {
    div = document.getElementById('div_created');
    div.style.display = 'none';
}
function showCreatedDiv() {
    div = document.getElementById('div_created');
    div.style.display = 'block';
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


function setBoardLink(url) {
    a = document.getElementById('link');
    a.href = url;
    a.text = url;
}
// Show board link functions -- END

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