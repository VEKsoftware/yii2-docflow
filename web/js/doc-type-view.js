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
        checkAjax(
            $upTreeButton.data("href"),
            $upTreeButton.data("name"),
            $upTreeButton.data("fl-tree-url"),
            'Up'
        );
    });

    $downTreeButton.on('click', function () {
        checkAjax(
            $downTreeButton.data("href"),
            $downTreeButton.data("name"),
            $downTreeButton.data("fl-tree-url"),
            'Down'
        );
    });

    $rightTreeButton.on('click', function () {
        checkAjax(
            $rightTreeButton.data("href"),
            $rightTreeButton.data("name"),
            $rightTreeButton.data("fl-tree-url"),
            'Right'
        );
    });

    $leftTreeButton.on('click', function () {
        checkAjax(
            $leftTreeButton.data("href"),
            $leftTreeButton.data("name"),
            $leftTreeButton.data("fl-tree-url"),
            'Left'
        );
    });
});

/**
 * Проверяем, испольняется ли в данное время Ajax запрос и если исполняется,
 * то не даем возможности запустить слудующий
 * @param url
 * @param name
 * @param flTreeUrl
 * @param action
 */
function checkAjax(url, name, flTreeUrl, action) {
    var $treeActionButtons = $(document).find("#actions-tree-buttons");

    if (!$treeActionButtons.hasClass('ajax-disabled')) {
        getAjax(url, name, flTreeUrl, action);
    }
}

/**
 * Соверашаем Ajax запрос по переданноу Url
 * @param url
 * @param name
 * @param flTreeUrl
 * @param action
 */
function getAjax(url, name, flTreeUrl, action) {
    blockButtons();
    clearTreeChangeStatus();
    prepareTree(url, name, flTreeUrl, action);
    unblockButtons();
}

/**
 *
 * @param url
 * @param name
 * @param flTreeUrl
 * @param action
 */
function prepareTree(url, name, flTreeUrl, action) {
    var tree = $('#tree').treeview(true);
    var currentNode = tree.findNodes('^' + name + '$', 'text')[0];
    var nodesOnLevel = tree.findNodes('^' + currentNode.level + '$', 'level');
    var next = nodesOnLevel[(nodesOnLevel.length - 1)];

    /* Если нам нужно переместить вниз, а ниже только элемент для подгрузки следующих документов,
     то подгружаем сначала документы, а потом производим запрос на изменение */
    if ((action === 'Down') && ((next.index - currentNode.index) === 1) && (next.href_next)) {
        $.get(next.href_next, function (vars) {
            var parent = getParentByParentId(tree, next);

            tree.removeNode(next, {silent: true});
            tree.addNode(vars, parent, false, {silent: true});

            ajaxActionRequest(url, name, flTreeUrl, action)
        });
    } else {
        ajaxActionRequest(url, name, flTreeUrl, action);
    }
}

/**
 * Выполняем ajax запрос на действие
 * @param url
 * @param name
 * @param flTreeUrl
 * @param action
 */
function ajaxActionRequest(url, name, flTreeUrl, action) {
    $.get(url, function (data) {
        if (data.error !== undefined) {
            setTreeChangeStatus('error', data.error);
        } else {
            setTreeChangeStatus('success', data.success);
            renderTree(name, flTreeUrl, action);
        }
    });
}

/**
 * Перестраиваем древо статусов
 * @param currentName
 * @param flTreeUrl
 * @param action
 */
function renderTree(currentName, flTreeUrl, action) {

    if ((action === 'Up') || (action === 'Down')) {
        nodeVertical(currentName, action);
    }

    if (action === 'Right') {
        nodeIn(flTreeUrl, currentName, action);
    }

    if (action === 'Left') {
        nodeOut(flTreeUrl, currentName, action);
    }
}

/**
 * Перемещаем ноду на 1 положение:
 * 1)Если action - Up - то выше
 * 2)Если action - Down - то ниже
 * @param currentName
 * @param action
 */
function nodeVertical(currentName, action) {
    var tree = $('#tree').treeview(true);
    var currentNode = tree.findNodes('^' + currentName + '$', 'text')[0];
    var needIndex = (action === 'Up')
        ? currentNode.index - 1
        : currentNode.index + 1;

    /* Удаляем текущюю ноду */
    tree.removeNode(currentNode, {silent: true});

    /* Определяем родителя */
    var parent = getParentByParentId(tree, currentNode);

    /* Формируем структуру для добавления из текущей ноды */
    var newCurrentNode = {
        text: currentNode.text,
        href: currentNode.href,
        href_child: currentNode.href_child,
        tags: currentNode.tags
    };

    /* Добавляем текущюю ноду в необходимое место */
    tree.addNode(newCurrentNode, parent, needIndex, {silent: true});

    tree.selectNode(newCurrentNode);
}

/**
 * Перемещаем ноду во внутренний уровень вверхстоящей ноды
 * @param flTreeUrl
 * @param currentName
 * @param action
 */
function nodeIn(flTreeUrl, currentName, action) {
    var tree = $('#tree').treeview(true);
    var currentNode = tree.findNodes('^' + currentName + '$', 'text')[0];

    nodeHorizontal(currentNode, flTreeUrl, action);
}

/**
 * Перемещаем ноду во внешний уровень к родительской ноде
 * @param flTreeUrl
 * @param currentName
 * @param action
 */
function nodeOut(flTreeUrl, currentName, action) {
    var tree = $('#tree').treeview(true);
    var currentNode = tree.findNodes('^' + currentName + '$', 'text')[0];
    var parentNode = tree.findNodes('^' + currentNode.parentId + '$', 'nodeId')[0];

    nodeHorizontal(parentNode, flTreeUrl, action);
}

/**
 * Если внешний уровень корневой, то перезагружаем дерево, если не уорневой то перезагружаем родительскую ноду
 * @param node
 * @param flTreeUrl
 * @param action
 */
function nodeHorizontal(node, flTreeUrl, action) {
    var tree = $('#tree').treeview(true);

    /* Cмотрим на родителя перемещаемой ноды */
    if (node.parentId !== undefined) {
        var reloadNode = tree.findNodes('^' + node.parentId + '$', 'nodeId')[0];

        tree.removeNode(reloadNode, {silent: true});

        /* Определяем родителя */
        var parent = getParentByParentId(tree, reloadNode);

        var int = (action === 'Right')
            ? parseInt(reloadNode.tags[0]) - 1
            : parseInt(reloadNode.tags[0]) + 1;

        var newReloadNode = {
            text: reloadNode.text,
            href: reloadNode.href,
            href_child: reloadNode.href_child,
            tags: [int, reloadNode.tags[1]]
        };

        tree.addNode(newReloadNode, parent, reloadNode.index, {silent: true});

        tree.selectNode(newReloadNode);
    } else {
        tree.remove();
        initFlTree(flTreeUrl);
    }
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

function getLeaf(item) {
    if (item.href !== location.pathname) {
        var leaf = $("#tree-leaf");

        leaf.load(item.href, function () {
            $("#tree-leaf").trigger("domChanged");
        });
    }
}

function initFlTree(dataUrl, showCheckbox) {
    var onSelect = function (event, item) {
        var tree = $('#tree').treeview(true);

        /* Очищаем сообщения о результате операции (перемещения по дереву) */
        clearTreeChangeStatus();

        /* Еcли есть ссылка для загрузки вложенных документов и если документы еще не загружены,
         то загружаем и устанавливаем документы в дерево и загружаем дерево с простыми связями для текущего документа */
        if (item.href_child && item.nodes === undefined) {
            $.get(item.href_child, function (vars) {
                var parent = tree.findNodes(item.text, 'text')[0];
                tree.addNode(vars, parent, 0, {silent: true});
            });
        }

        /* Если есть адрес для загрузки оставшихся документов на уровне, то загружаем их и устанавливаем в дерево */
        if (item.href_next) {
            $.get(item.href_next, function (vars) {
                var parent = getParentByParentId(tree, item);

                tree.removeNode(item, {silent: true});
                tree.addNode(vars, parent, false, {silent: true});
            });
        }
    };

    var onUnSelect = function (event, item) {
        /* Очищаем сообщения о результате операции (перемещения по дереву) */
        clearTreeChangeStatus();
    };

    var onCollapsed = function (event, item) {
        var tree = $('#tree').treeview(true);

        /* Очищаем сообщения о результате операции (перемещения по дереву) */
        clearTreeChangeStatus();

        var currentNode = {
            'text': item.text,
            'href_child': item.href_child,
            'tags': item.tags
        };

        var parent = getParentByParentId(tree, item);

        tree.removeNode(item, {silent: true});
        tree.addNode(currentNode, parent, item.index, {silent: true});
    };

    $('#tree').treeview({
        dataUrl: {
            url: dataUrl
        },
        showCheckbox: showCheckbox,
        levels: 5,
        showTags: true,
        onNodeSelected: onSelect,
        onNodeUnselected: onUnSelect,
        onNodeCollapsed: onCollapsed
    });
}

function initFlTreeWithLeaf(dataUrl, showCheckbox) {
    var onSelect = function (event, item) {
        var tree = $('#tree').treeview(true);

        /* Очищаем сообщения о результате операции (перемещения по дереву) */
        clearTreeChangeStatus();

        /* Еcли есть ссылка для загрузки вложенных документов и если документы еще не загружены,
         то загружаем и устанавливаем документы в дерево и загружаем дерево с простыми связями для текущего документа */
        if (item.href_child && item.nodes === undefined) {
            $.get(item.href_child, function (vars) {
                var parent = tree.findNodes(item.text, 'text')[0];
                tree.addNode(vars, parent, 0, {silent: true});

                getLeaf(item);
            });
        }

        /* Если нет адреса для загрузки подчиненных и оставшихся  документов, то загружаем дерево с простыми связями для текущего документа */
        if ((item.href_child === undefined) || (item.href_next === undefined)) {
            getLeaf(item);
        }

        /* Если есть адрес для загрузки оставшихся документов на уровне, то загружаем их и устанавливаем в дерево */
        if (item.href_next) {
            $.get(item.href_next, function (vars) {
                var parent = getParentByParentId(tree, item);

                tree.removeNode(item, {silent: true});
                tree.addNode(vars, parent, false, {silent: true});
            });
        }
    };

    var onUnSelect = function (event, item) {
        /* Очищаем сообщения о результате операции (перемещения по дереву) */
        clearTreeChangeStatus();

        $("#tree-leaf").html('');
    };

    var onCollapsed = function (event, item) {
        var tree = $('#tree').treeview(true);

        /* Очищаем сообщения о результате операции (перемещения по дереву) */
        clearTreeChangeStatus();

        var currentNode = {
            'text': item.text,
            'href': item.href,
            'href_child': item.href_child,
            'tags': item.tags
        };

        var parent = getParentByParentId(tree, item);

        tree.removeNode(item, {silent: true});
        tree.addNode(currentNode, parent, item.index, {silent: true});
    };

    $('#tree').treeview({
        dataUrl: {
            url: dataUrl
        },
        showCheckbox: showCheckbox,
        levels: 5,
        showTags: true,
        onNodeSelected: onSelect,
        onNodeUnselected: onUnSelect,
        onNodeCollapsed: onCollapsed
    });
}

function initFlTreeWithSimpleLinks(dataUrl, showCheckbox) {
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
                var parent = getParentByParentId(tree, item);

                tree.removeNode(item, {silent: true});
                tree.addNode(vars, parent, false, {silent: true});
            });
        }
    };

    var onCollapsed = function (event, item) {
        var tree = $('#tree-simple-link').treeview(true);

        item.state.selected = false;

        var currentNode = {
            'text': item.text,
            'href_child': item.href_child,
            'href_addSimple': item.href_addSimple,
            'href_delSimple': item.href_delSimple,
            'tags': item.tags,
            'state': item.state
        };

        var parent = getParentByParentId(tree, item);

        tree.removeNode(item, {silent: true});
        tree.addNode(currentNode, parent, item.index, {silent: true});
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
            tree.uncheckNode(item, {silent: true});
        }
    };

    var onRendered = function (event, item) {
        /* Удаляем чекбоксы там где они не нужны */
        hideCheckedIconWithParentNode(item);
        hideCheckedIconWithNext(item);
    };

    $('#tree-simple-link').treeview({
        dataUrl: {
            'url': dataUrl
        },
        showCheckbox: showCheckbox,
        levels: 5,
        showTags: true,
        onNodeChecked: onChecked,
        onNodeUnchecked: onUnchecked,
        onNodeSelected: onSelected,
        onNodeCollapsed: onCollapsed,
        onNodeRendered: onRendered
    });
}

/**
 * Убираем "чекбокс" у элемента, который равен документу "от"
 * @param item
 */
function hideCheckedIconWithParentNode(item) {
    var tree = $('#tree');
    var parentNode = (tree.treeview('getSelected'))
        ? tree.treeview('getSelected')[0]
        : undefined;

    if ((parentNode !== undefined) && (parentNode.text === item.text)) {
        hideCheckedIcon(item);
    }
}

/**
 * Убираем "чекбокс" у элемента, который подгружает оставшиеся элементы
 * @param item
 */
function hideCheckedIconWithNext(item) {
    if (item.href_next) {
        hideCheckedIcon(item);
    }
}

/**
 * Прячем "чекбокс"
 * @param item
 */
function hideCheckedIcon(item) {
    var treeSL = $('#tree-simple-link');
    var nodeSLHtml = treeSL.find("li[data-nodeid = '" + item.nodeId + "']");
    var nodeSLCheckboxHtml = nodeSLHtml.find("span[class *= check-icon]");

    nodeSLCheckboxHtml.hide();
}

/**
 * Получаем родительскую ноду, по её nodeId, используя значения из parentId передаваемой ноды
 * @param tree
 * @param currentNode
 * @returns {boolean}
 */
function getParentByParentId(tree, currentNode) {
    /* Определяем родителя */
    var parent = false;

    if (currentNode.parentId !== undefined) {
        parent = tree.findNodes('^' + currentNode.parentId + '$', 'nodeId')[0];
    }

    return parent;
}
