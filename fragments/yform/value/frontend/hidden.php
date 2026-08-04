<?php

/**
 * Frontend variant of the "hidden" value template.
 *
 * Ships as a plain hand-over to the backend markup. Replace the delegate call
 * with your own markup to give the frontend a different look; the fragment
 * variables are the same ones the backend file receives.
 *
 * @var \Yakamara\YForm\View\Fragment $this
 */
echo $this->delegate('yform/value/backend/hidden.php');
