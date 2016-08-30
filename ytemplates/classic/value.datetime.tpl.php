<p class="<?php echo $this->getHTMLClass() ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="select <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>" ><?php echo $this->getLabel() ?></label>

    <?php

    $output = $format;

    $search = [];
    $replace = [];

    $pos = strpos($format, "YYYY");
    if ($pos !== false) {
        $replace_i = '<select id="'.$this->getFieldId('year').'" name="'.$this->getFieldName('year').'" class="'.$this->getWarningClass().'" size="1">';
        $replace_i .= '<option value="00">--</option>';
        for ($i = $yearStart; $i <= $yearEnd; ++$i):
            $selected = (@$year == $i) ? ' selected="selected"' : '';
            $replace_i .= '<option value="'.$i.'"'. $selected .'>'.$i.'</option>';
        endfor;
        $replace_i .= '</select>';
        $replace['YYYY'] = $replace_i;
        $search[] = 'YYYY';
    }

    $pos = strpos($format, "MM");
    if ($pos !== false) {
        $replace_i = '<select id="'.$this->getFieldId('month').'" name="'.$this->getFieldName('month').'" class="'.$this->getWarningClass().'" size="1">';
        $replace_i .= '<option value="00">--</option>';
        for ($i = 1; $i < 13; ++$i):
            $selected = (@$month == $i) ? ' selected="selected"' : '';
            $replace_i .= '<option value="'.$i.'"'. $selected .'>'.$i.'</option>';
        endfor;
        $replace_i .= '</select>';
        $replace['MM'] = $replace_i;
        $search[] = 'MM';
    }

    $pos = strpos($format, "DD");
    if ($pos !== false) {
        $replace_i = '<select id="'.$this->getFieldId('day').'" name="'.$this->getFieldName('day').'" class="'.$this->getWarningClass().'" size="1">';
        $replace_i .= '<option value="00">--</option>';
        for ($i = 1; $i < 32; ++$i):
            $selected = (@$day == $i) ? ' selected="selected"' : '';
            $replace_i .= '<option value="'.$i.'"'. $selected .'>'.$i.'</option>';
        endfor;
        $replace_i .= '</select>';
        $replace['DD'] = $replace_i;
        $search[] = 'DD';
    }

    $pos = strpos($format, "HH");
    if ($pos !== false) {
        $replace_i = '<select id="'.$this->getFieldId('hour').'" name="'.$this->getFieldName('hour').'" class="'.$this->getWarningClass().'" size="1">';
        foreach ($hours as $i):
            $selected = (@$hour == $i) ? ' selected="selected"' : '';
            $replace_i .= '<option value="'.$i.'"'. $selected .'>'.$i.'</option>';
        endforeach;
        $replace_i .= '</select>';
        $replace['HH'] = $replace_i;
        $search[] = 'HH';
    }

    $pos = strpos($format, "ii");
    if ($pos !== false) {
        $replace_i = '<select id="'.$this->getFieldId('minute').'" name="'.$this->getFieldName('minute').'" class="'.$this->getWarningClass().'" size="1">';
        foreach ($minutes as $i):
            $selected = (@$minute == $i) ? ' selected="selected"' : '';
            $replace_i .= '<option value="'.$i.'"'. $selected .'>'.$i.'</option>';
        endforeach;
        $replace_i .= '</select>';
        $replace['ii'] = $replace_i;
        $search[] = 'ii';
    }

    $pos = strpos($format, "ss");
    if ($pos !== false) {
        $replace_i = '<select id="'.$this->getFieldId('second').'" name="'.$this->getFieldName('second').'" class="'.$this->getWarningClass().'" size="1">';
        for ($i=0; $i<60; $i++):
            $selected = (@$second == $i) ? ' selected="selected"' : '';
            $replace_i .= '<option value="'.$i.'"'. $selected .'>'.$i.'</option>';
        endfor;
        $replace_i .= '</select>';
        $replace['ss'] = $replace_i;
        $search[] = 'ss';
    }

    // $output = str_replace($search, $replace, $output);
    $output = strtr($output, $replace);

    echo $output;

    ?>
</p>
