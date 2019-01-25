<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>REST API</title>

    <link href="https://fonts.googleapis.com/css?family=Lato|Ubuntu+Mono" rel="stylesheet">

    <style>

        body {
            font-family: 'Lato', sans-serif;
            font-family: 'Ubuntu Mono', monospace;
            font-size: 1rem;
            line-height: 1.3rem;
            background-color: #eeeeee;
        }

        .model {
            display:block;
            margin: 0 30px;
        }

        .name {

            padding: 5px;
            font-size: 1.2rem;
            line-height: 2rem;
            font-weight: bold;
            text-align: center;
            background-color: #ddd;
        }

        .rest-type {
            clear:both;
            width:100%;
            background-color: #fff;
        }

        .method {
            padding:5px;
            display:inline-block;
            width:50px;
            float:left;
            text-align: center;
         }

        .path {
            padding:5px;
            padding-left: 10px;
            display:inline-block;
        }

        .rest-delete .method{
            background-color: #ED6A5A;
        }

        .rest-get .method{
            background-color: #3CAAB5;
        }

        .rest-post .method{
            background-color: #78BC61;
        }

        .rest-parameter {
            clear:both;
            width:100%;
            background-color: #fff;
            margin-bottom: 10px;
        }

        .rest-parameter .type {
            font-style: italic;
            padding:10px;
        }

        .body {
            padding:10px;
        }

        .fields {
            padding:10px;
        }

        h1 {
            text-align: center;
        }

    </style>

</head>
<body>

<h1>REST API</h1>

<?php

foreach ($this->elements as $element) {
    echo '<div class="model">
        <div class="name">'.$element['model'].'</div>';
    foreach ($element['types'] as $type) {
        echo '<div class="types">'.$type.'</div>';
    }
    echo '</div>';
}

?>

</body>
</html>
