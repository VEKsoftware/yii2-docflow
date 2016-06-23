/**
 * Created by dolgikh on 03.06.16.
 */

$("#tree-leaf").bind("domChanged", function () {
    var $upTreeButton = $(document).find("[name='up-in-tree']");
    var $downTreeButton = $(document).find("[name='down-in-tree']");
    var $rightTreeButton = $(document).find("[name='right-in-tree']");
    var $leftTreeButton = $(document).find("[name='left-in-tree']");

    /**
     * Назначаем обработчики при клике
     */
    $upTreeButton.on('click', function () {
        checkAjax($upTreeButton.data("href"), $upTreeButton.data("doc-type"), $upTreeButton.data("status-tag"));
    });

    $downTreeButton.on('click', function () {
        checkAjax($downTreeButton.data("href"), $downTreeButton.data("doc-type"), $downTreeButton.data("status-tag"));
    });

    $rightTreeButton.on('click', function () {
        checkAjax($rightTreeButton.data("href"), $rightTreeButton.data("doc-type"), $rightTreeButton.data("status-tag"));
    });

    $leftTreeButton.on('click', function () {
        checkAjax($leftTreeButton.data("href"), $leftTreeButton.data("doc-type"), $leftTreeButton.data("status-tag"));
    });
});

/**
 * Проверяем, испольняется ли в данное время Ajax запрос и если исполняется,
 * то не даем возможности запустить слудующий
 * @param url
 * @param docTag
 * @param statusTag
 */
function checkAjax(url, docTag, statusTag) {
    var $treeActionButtons = $(document).find("#actions-tree-buttons");

    if (!$treeActionButtons.hasClass('ajax-disabled')) {
        getAjax(url, docTag, statusTag);
    }
}

/**
 * Соверашаем Ajax запрос по переданноу Url
 * @param url
 * @param docTag
 * @param statusTag
 */
function getAjax(url, docTag, statusTag) {
    blockButtons();
    clearTreeChangeStatus();
    $.get(url, function (data) {
        if (data.error !== undefined) {
            setTreeChangeStatus('error', data.error);
        } else {
            setTreeChangeStatus('success', data.success);
            setTree(docTag, statusTag);
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
function setTree(docTag, statusTag) {
    var url = '/docflow/doc-types/ajax-tree?docTag=' + docTag + '&statusTag=' + statusTag;
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
        levels: 5,
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
            $tree.treeview('uncheckNode', [node.nodeId, {silent: true}]);
        } else {
            $tree.treeview('checkNode', [node.nodeId, {silent: true}]);
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
