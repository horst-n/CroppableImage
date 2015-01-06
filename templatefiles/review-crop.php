<div id='results'>
    <img src='<?php echo $targetUrl ?>' alt='Cropped image' />
    <ul>
        <li>
            <button class='ui-button ui-widget ui-corner-all head_button_clone ui-state-default' onclick="window.close();">
                <?php echo $confirmCropText; ?>
            </button>
        </li>
        <?php if ($suffix): ?>
            <li>
                <a class='modal' href='<?php echo $backToCropUrl ?>'><?php echo $cropAgainText; ?></a>
            </li>
        <?php endif ?>
    </ul>
</div>