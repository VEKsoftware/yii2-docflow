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
        checkAjax($upTreeButton.data("href"), $upTreeButton.data("fl-tree-url"), $upTreeButton.data("fl-tree-with-simple-url"));
    });

    $downTreeButton.on('click', function () {
        checkAjax($downTreeButton.data("href"), $upTreeButton.data("fl-tree-url"), $upTreeButton.data("fl-tree-with-simple-url"));
    });

    $rightTreeButton.on('click', function () {
        checkAjax($rightTreeButton.data("href"), $upTreeButton.data("fl-tree-url"), $upTreeButton.data("fl-tree-with-simple-url"));
    });

    $leftTreeButton.on('click', function () {
        checkAjax($leftTreeButton.data("href"), $upTreeButton.data("fl-tree-url"), $upTreeButton.data("fl-tree-with-simple-url"));
    });
});

/**
 * Проверяем, испольняется ли в данное время Ajax запрос и если исполняется,
 * то не даем возможности запустить слудующий
 * @param url
 * @param flTreeUrl
 * @param flTreeWithSimpleUrl
 */
function checkAjax(url, flTreeUrl, flTreeWithSimpleUrl) {
    var $treeActionButtons = $(document).find("#actions-tree-buttons");

    if (!$treeActionButtons.hasClass('ajax-disabled')) {
        getAjax(url, flTreeUrl, flTreeWithSimpleUrl);
    }
}

/**
 * Соверашаем Ajax запрос по переданноу Url
 * @param url
 * @param flTreeUrl
 * @param flTreeWithSimpleUrl
 */
function getAjax(url, flTreeUrl, flTreeWithSimpleUrl) {
    blockButtons();
    clearTreeChangeStatus();
    $.get(url, function (data) {
        if (data.error !== undefined) {
            setTreeChangeStatus('error', data.error);
        } else {
            setTreeChangeStatus('success', data.success);
            renderTree(flTreeUrl, flTreeWithSimpleUrl);
        }
    });

    unblockButtons();
}

/**
 * Перестраиваем древо статусов
 */
function renderTree(flTreeUrl, flTreeWithSimpleUrl) {
    var tree = $('#tree').treeview(true);
    var flTreeWithSimple = $('#tree-simple-link').treeview(true);

    tree.remove();
    flTreeWithSimple.remove();

    initFlTree(flTreeUrl);
    initFlTreeWithSimpleLinks(flTreeWithSimpleUrl);
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
 * Соверашаем Ajax запрос по переданноу Url
 * @param url
 * @param item
 */
function getSimpleLinksAjax(url, item) {
    clearChangeStatusSimpleLink();

    $.get(url, function (data) {
        if (data.error !== undefined) {
            setChangeStatusSimpleLink('error', data.error);
            changeSimpleLinkCheckbox(true, item)
        } else {
            setChangeStatusSimpleLink('success', data.success);
            changeSimpleLinkCheckbox(false, item)
        }
    });
}

/**
 * Меняем статусы если ответ ajax ['false' => .....] false
 * @param invert
 * @param item
 */
function changeSimpleLinkCheckbox(invert, item) {
    var $tree = $('#tree-simple-link').treeview(true);

    if (invert === true) {
        if (item.state.checked === true) {
            $tree.uncheckNode(item, {silent: true});
        } else {
            $tree.checkNode(item, {silent: true});
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

function initFlTree(dataUrl) {
    var onSelect = function (event, item) {
        var tree = $('#tree').treeview(true);

        if (item.href_child && item.nodes === undefined) {
            $.get(item.href_child, function (vars) {
                var parent = tree.findNodes(item.text, 'text')[0];
                tree.addNode(vars, parent, 0, {silent: true});
            });
        }

        if (item.href_next) {
            $.get(item.href_next, function (vars) {
                var parrent = false;

                if (item.parentId !== undefined) {
                    parrent = tree.findNodes(item.parentId, 'nodeId')[0];
                }

                tree.removeNode(item, {silent: true});
                tree.addNode(vars, parrent, false, {silent: true});
            });
        }

        if (item.href !== location.pathname) {
            $("#tree-leaf").load(item.href, function () {
                $("#tree-leaf").trigger("domChanged");
            });
        }
    };

    var onUnselect = function (event, item) {
        $("#tree-leaf").html('');
    };

    var onCollapsed = function (event, item) {
        var tree = $('#tree').treeview(true);
        var currentNode = {
            'text': item.text,
            'href': item.href,
            'href_child': item.href_child,
            'tags': item.tags
        };
        var currentIndex = item.index;
        var parrent = false;

        if (item.parentId !== undefined) {
            parrent = tree.findNodes(item.parentId, 'nodeId')[0];
        }

        tree.removeNode(item, {silent: true});
        tree.addNode(currentNode, parrent, currentIndex, {silent: true});
    };

    $('#tree').treeview({
        dataUrl: {
            url: dataUrl
        },
        levels: 5,
        showTags: true,
        onNodeSelected: onSelect,
        onNodeUnselected: onUnselect,
        onNodeCollapsed: onCollapsed
    });
}

function initFlTreeWithSimpleLinks(dataUrl) {
    var onSelected = function (event, item) {
        var tree = $('#tree-simple-link').treeview(true);

        if (item.href_child && item.nodes === undefined) {
            $.get(item.href_child, function (vars) {
                var parent = tree.findNodes(item.text, 'text')[0];
                tree.addNode(vars, parent, 0, {silent: true});
            });
        }

        if (item.href_next) {
            $.get(item.href_next, function (vars) {
                var parrent = false;

                if (item.parentId !== undefined) {
                    parrent = tree.findNodes(item.parentId, 'nodeId')[0];
                }

                tree.removeNode(item, {silent: true});
                tree.addNode(vars, parrent, false, {silent: true});
            });
        }
    };

    var onCollapsed = function (event, item) {
        var tree = $('#tree-simple-link').treeview(true);

        item.state.selected = false;

        var currentNode = {
            'text': item.text,
            'href': item.href,
            'href_child': item.href_child,
            'href_addSimple': item.href_addSimple,
            'href_delSimple': item.href_delSimple,
            'backColor': item.backColor,
            'tags': item.tags,
            'state': item.state
        };
        var currentIndex = item.index;
        var parrent = false;

        if (item.parentId !== undefined) {
            parrent = tree.findNodes(item.parentId, 'nodeId')[0];
        }

        tree.removeNode(item, {silent: true});
        tree.addNode(currentNode, parrent, currentIndex, {silent: true});
    };

    var onChecked = function (event, item) {
        var tree = $('#tree-simple-link').treeview(true);

        if (item.href_addSimple) {
            getSimpleLinksAjax(item.href_addSimple, item);
        } else {
            tree.uncheckNode(item, {silent: true});
        }
    };

    var onUnchecked = function (event, item) {
        var tree = $('#tree-simple-link').treeview(true);

        if (item.href_delSimple) {
            getSimpleLinksAjax(item.href_delSimple, item);
        } else {
            tree.checkNode(item, {silent: true});
        }
    };

    $('#tree-simple-link').treeview({
        dataUrl: {
            'url': dataUrl
        },
        showCheckbox: true,
        levels: 5,
        showTags: true,
        onNodeChecked: onChecked,
        onNodeUnchecked: onUnchecked,
        onNodeSelected: onSelected,
        onNodeCollapsed: onCollapsed
    });
}
