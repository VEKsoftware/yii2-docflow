/**
 * Created by dolgikh on 03.06.16.
 */

$("#tree-leaf").bind("domChanged", function () {
    var $upTreeButton = $(document).find("[name='up-in-tree']");
    var $downTreeButton = $(document).find("[name='down-in-tree']");
    var $rightTreeButton = $(document).find("[name='right-in-tree']");
    var $liftTreeButton = $(document).find("[name='left-in-tree']");

    /**
     * Назначаем обработчики при клике
     */
    $upTreeButton.on('click', function () {
        checkAjax($upTreeButton.data("href"));
    });

    $downTreeButton.on('click', function () {
        checkAjax($downTreeButton.data("href"));
    });

    $rightTreeButton.on('click', function () {
        checkAjax($rightTreeButton.data("href"));
    });

    $liftTreeButton.on('click', function () {
        checkAjax($liftTreeButton.data("href"));
    });
});

/**
 * Проверяем, испольняется ли в данное время Ajax запрос и если исполняется,
 * то не даем возможности запустить слудующий
 * @param url
 */
function checkAjax(url) {
    var $treeActionButtons = $(document).find("#actions-tree-buttons");

    if (!$treeActionButtons.hasClass('ajax-disabled')) {
        getAjax(url);
    }
}

/**
 * Соверашаем Ajax запрос по переданноу Url
 * @param url
 */
function getAjax(url) {
    blockButtons();
    clearTreeChangeStatus();
    $.get(url, function (data) {
        if (data.error !== undefined) {
            setTreeChangeStatus('error', data.error);
        } else {
            setTreeChangeStatus('success', data.success);
            setTree();
        }
    });

    unblockButtons();
}

/**
 * Получаем имя перемещаемого статуса
 * @returns {*}
 */
function getSelectedStatusName() {
    var selected = $('#tree').treeview('getSelected');

    return selected[0].text;
}

/**
 * Получаем NodeId статуса, с которым будем меняться местами
 * @param name
 * @returns {*}
 */
function selectCurrentStatus(name) {
    var status = $('#tree').find("li:contains('" + name + "')");
    return status.data('nodeid');
}

/**
 * Перестраиваем древо статусов
 */
function setTree() {
    var docTag = getUrlParameter('doc');
    var url = '/docflow/doc-types/ajax-tree?docTag=' + docTag;
    $.get(url, function (data) {
        renderTree(data);
    });
}

/**
 * Блокируем элементы на время Ajax запроса
 */
function blockButtons() {
    var $treeActionButtons = $(document).find("#actions-tree-buttons");

    $treeActionButtons.addClass('ajax-disabled');
}

/**
 * Разблокируем элемениты на время Ajax запроса
 */
function unblockButtons() {
    var $treeActionButtons = $(document).find("#actions-tree-buttons");

    $treeActionButtons.removeClass('ajax-disabled');
}

/**
 * Отображаем итог перемещения
 * @param status
 * @param text
 */
function setTreeChangeStatus(status, text) {
    var $statusTreeChange = $(document).find("#tree-change-status");

    if (status === 'success') {
        $statusTreeChange.text(text).addClass('success-tree-change').removeClass('error-tree-change');
    } else {
        $statusTreeChange.text(text).addClass('error-tree-change').removeClass('success-tree-change');
    }
}

/**
 * Очищаем итог перемещения
 */
function clearTreeChangeStatus() {
    var $statusTreeChange = $(document).find("#tree-change-status");

    $statusTreeChange.text('');
}

/**
 * Устанавливаем дерево
 * @param data
 */
function renderTree(data) {
    var $tree = $('#tree');
    var name = getSelectedStatusName();

    var onSelect = function (undefined, item) {
        if (item.href !== location.pathname) {
            $("#tree-leaf").load(item.href, function () {
                $("#tree-leaf").trigger("domChanged");
            });
        }
    };

    var onUnSelect = function (undefined, item) {
        $("#tree-leaf").html('');
    };

    $tree.treeview({
        data: data,
        onNodeSelected: onSelect,
        onNodeUnselected: onUnSelect
    });

    selectStatusForName(name);
}

/**
 * После рендера дерева выделяем статус который перемещался
 * @param name
 */
function selectStatusForName(name) {
    var $tree = $('#tree');
    var nodeId = selectCurrentStatus(name);

    $tree.treeview('selectNode', [nodeId, {silent: true}]);
}


/**
 * Получаем get параметер из url
 * @param sParam
 * @returns {boolean}
 */
function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
}

/**
 * Соверашаем Ajax запрос по переданноу Url
 * @param url
 */
function getSimpleLinksAjax(url) {
    clearChangeStatusSimpleLink();
    
    $.get(url, function (data) {
        if (data.error !== undefined) {
            setChangeStatusSimpleLink('error', data.error);
            changeSimpleLinkCheckbox(true)
        } else {
            setChangeStatusSimpleLink('success', data.success);
            changeSimpleLinkCheckbox(false)
        }
    });
}

/**
 * Меняем статусы если ответ ajax ['false' => .....] false
 * @param invert
 */
function changeSimpleLinkCheckbox(invert) {
    var $tree = $('#tree-simple-link');
    var nodes = $tree.treeview('getSelected');
    var node = nodes[0];

    if (invert === true) {
        if (node.state.checked === true) {
            $tree.treeview('uncheckNode', [node.nodeId, { silent: true }]);
        }  else {
            $tree.treeview('checkNode', [node.nodeId, { silent: true }]);
        }
    }
}

function setChangeStatusSimpleLink(status, text) {
    var $changeStatusSimpleLink = $(document).find("span#simple-link-change-status");

    if (status === 'success') {
        $changeStatusSimpleLink
            .text(text)
            .addClass('success-simple-link-change')
            .removeClass('error-simple-link-change');
    } else {
        $changeStatusSimpleLink
            .text(text)
            .addClass('error-simple-link-change')
            .removeClass('success-simple-link-change');
    }
}

function clearChangeStatusSimpleLink() {
    var $changeStatusSimpleLink = $(document).find("span#simple-link-change-status");

    $changeStatusSimpleLink.text('');
}
