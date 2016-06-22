<p class="<?php echo $this->getHTMLClass() ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="select <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabel() ?></label>

    <?php

        foreach ($layout as $component):
            switch ($component):
                case '###Y###':
                    ?><select id="<?php echo $this->getFieldId('year') ?>" name="<?php echo $this->getFieldName('year') ?>" class="<?php echo $this->getWarningClass() ?>" size="1">
                        <option value="00">--</option>
                        <?php for ($i = $yearStart; $i <= $yearEnd; ++$i): ?>
                            <option value="<?php echo $i ?>"<?php echo $year == $i ? ' selected="selected"' : '' ?>><?php echo $i ?></option>
                        <?php endfor ?>
                    </select><?php
                    break;

                case '###M###':
                    ?><select id="<?php echo $this->getFieldId('month') ?>" name="<?php echo $this->getFieldName('month') ?>" class="<?php echo $this->getWarningClass() ?>" size="1">
                        <option value="00">--</option>
                        <?php for ($i = 1; $i < 13; ++$i): ?>
                            <option value="<?php echo $i ?>"<?php echo $month == $i ? ' selected="selected"' : '' ?>><?php echo $i ?></option>
                        <?php endfor ?>
                    </select><?php
                    break;

                case '###D###':
                    ?><select id="<?php echo $this->getFieldId('day') ?>" name="<?php echo $this->getFieldName('day') ?>" class="<?php echo $this->getWarningClass() ?>" size="1">
                        <option value="00">--</option>
                        <?php for ($i = 1; $i < 32; ++$i): ?>
                            <option value="<?php echo $i ?>"<?php echo $day == $i ? ' selected="selected"' : '' ?>><?php echo $i ?></option>
                        <?php endfor ?>
                    </select><?php
                    break;

                case '###H###':
                    ?><select id="<?php echo $this->getFieldId('hour') ?>" name="<?php echo $this->getFieldName('hour') ?>" class="<?php echo $this->getWarningClass() ?>" size="1">
                    <?php foreach ($hours as $i): ?>
                        <option value="<?php echo $i ?>"<?php echo $hour == $i ? ' selected="selected"' : '' ?>><?php echo $i ?></option>
                    <?php endforeach ?>
                    </select><?php
                    break;

                case '###I###':
                    ?><select id="<?php echo $this->getFieldId('min') ?>" name="<?php echo $this->getFieldName('min') ?>" class="<?php echo $this->getWarningClass() ?>" size="1">
                    <?php foreach ($minutes as $i): ?>
                        <option value="<?php echo $i ?>"<?php echo $minute == $i ? ' selected="selected"' : '' ?>><?php echo $i ?></option>
                    <?php endforeach ?>
                    </select><?php
                    break;

                default:
                    echo $component;
            endswitch;
        endforeach;
    ?>
</p>
