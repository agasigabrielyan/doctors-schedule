<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * @var $arResult
 */
use \Bitrix\Main\Ui\Extension;
Extension::load('ui.bootstrap4');
?>
<table class="table table-hover table__doctors-schedule">
    <?php foreach( $arResult['DOCTORS_SCHEDULES'] as $arKey => $arItem ): ?>
        <tr>
            <td class="doctors__td_first">
                <b><?= $arItem['USER_DATA']['NAME'] ?></b> (<?= $arItem['USER_DATA']['EMAIL'] ?>)
            </td>
            <td class="doctors__td_second">
                <?= $arItem['OUTPUT_HTML_FOR_WORK_DAYS']; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>



