<?php

/**
 * @var rex_yform_value_signature $this
 * @psalm-scope-this rex_yform_value_signature
 */

$value = $value ?? $this->getValue();

$notice = [];

if ('' != $this->getElement('notice')) {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()]) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$class_group = trim('form-group yform-element ' . $this->getWarningClass());
$class_label[] = 'control-label';

$field_before = '';
$field_after = '';
$specialAttributes = $this->getAttributeArray([]);

$attributes = [
    'class' => 'form-control signature',
    'name' => $this->getFieldName(),
    'type' => 'hidden',
    'id' => 'canvas-target-'. $this->getName(),
    'value' => $value,
];

$attributes = $this->getAttributeElements($attributes, ['placeholder', 'autocomplete', 'pattern', 'required', 'disabled', 'readonly']);

?>
<div class="canvas-signature <?= $class_group; ?>" id="<?= $this->getHTMLId(); ?>">
    <label class="<?= implode(' ', $class_label); ?>"><?= $this->getLabel(); ?></label>
    <div class="canvas">
        <div class="canvas-wrapper">
            <canvas id="canvas-<?= $this->getName(); ?>"></canvas>
            <?php if (isset($value) && '' != $value): ?>
                <img src="<?= $value; ?>">
            <?php endif; ?>
        </div>
        <input <?= implode(' ', $attributes); ?>>
        &nbsp; <button type="button" class="btn btn-primary" id="clear-<?= $this->getName(); ?>" onclick="eraseSignature_<?= $this->getName(); ?>()" title="Zeichenfläche leeren"><i class="rex-icon fa fa-eraser"></i></button>
    </div>
    <?php echo $notice; ?>
</div>
<style nonce="<?php echo rex_response::getNonce(); ?>">
    .canvas-signature div.canvas{
        position: relative;
        display: flex;
        flex-direction: row;
        align-items: center;
        align-content: flex-start;
    }
    .canvas-signature .canvas-wrapper {
        position: relative;
        width: 300px;
        height: 80px;
        background-color: #FFF;
    }
    .canvas-signature .canvas-wrapper canvas {
        width: 100%;
        height: 100%;
        background-color: transparent;
        position: relative;
        z-index: 1;
    }
    .canvas-signature .canvas-wrapper img {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        user-select: none;
        border: none;
        opacity: 0.1;
    }
</style>

<script nonce="<?php echo rex_response::getNonce(); ?>">
    if (typeof rex !== 'undefined' && rex.backend) {
        $(document).on("rex:ready", function(){
            initSignature_<?= $this->getName(); ?>();
        });
    } else {
        $(document).ready(function(){
            initSignature_<?= $this->getName(); ?>();
        });
    }

    function initSignature_<?= $this->getName(); ?>() {
        let base_id = '<?= $this->getName(); ?>',
            canvas = $("#canvas-"+ base_id)[0],
            target = $("#canvas-target-"+ base_id),
            ctx,
            flag = false,
            dot_flag = false,
            prevX = 0,
            currX = 0,
            prevY = 0,
            currY = 0,
            x = "black",
            y = 2;

        ctx = canvas.getContext("2d");
        w = canvas.width = $(canvas).width();
        h = canvas.height = $(canvas).height();

        canvas.addEventListener("mousedown", handleStart, false);
        canvas.addEventListener("mousemove", handleMove, false);
        canvas.addEventListener("mouseup", handleEnd, false);
        canvas.addEventListener("mouseout", handleCancel, false);

        canvas.addEventListener("touchstart", handleStart, false);
        canvas.addEventListener("touchmove", handleMove, false);
        canvas.addEventListener("touchend", handleEnd, false);
        canvas.addEventListener("touchcancel", handleCancel, false);

        function handleStart(evt) {
            evt.preventDefault();
            flag = true;
            dot_flag = true;
            if (evt.touches) {
                let touch = evt.touches[0];
                prevX = currX = touch.clientX;
                prevY = currY = touch.clientY;
            } else {
                prevX = currX = evt.clientX;
                prevY = currY = evt.clientY;
            }
            if (dot_flag) {
                ctx.beginPath();
                ctx.fillStyle = x;
                ctx.fillRect(currX, currY, 2, 2);
                ctx.closePath();
                dot_flag = false;
            }
        }

        function handleMove(evt) {
            evt.preventDefault();
            if (flag) {
                prevX = currX;
                prevY = currY;
                if (evt.touches) {
                    let touch = evt.touches[0];
                    currX = touch.clientX;
                    currY = touch.clientY;
                } else {
                    currX = evt.clientX;
                    currY = evt.clientY;
                }
                draw();
            }
        }

        function handleEnd(evt) {
            evt.preventDefault();
            flag = false;
        }

        function handleCancel(evt) {
            evt.preventDefault();
            flag = false;
        }

        function draw() {
            let offset = $(canvas).offset();
            let scrollTop = $("html").scrollTop();
            let scrollLeft = $("html").scrollLeft();
            ctx.beginPath();
            ctx.moveTo(prevX - offset.left + scrollLeft, prevY - offset.top + scrollTop);
            ctx.lineTo(currX - offset.left + scrollLeft, currY - offset.top + scrollTop);
            ctx.strokeStyle = x;
            ctx.lineWidth = y;
            ctx.stroke();
            ctx.closePath();
            // push result to input hidden
            target.val(canvas.toDataURL());
        }
    }

    function eraseSignature_<?= $this->getName(); ?>() {
        let m = confirm("Zeichenfläche wirklich löschen?");
        if (m) {
            $("#canvas-<?= $this->getName(); ?>")[0].getContext("2d").clearRect(0, 0, w, h);
        }
    }
</script>
