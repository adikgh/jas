<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?=core::csrf()?>">

<title><?=h($menu_title)?> | <?=h($site['name'])?></title>
<meta name="theme-color" content="#0a0f0c">
<meta name="robots" content="noindex, nofollow">

<!-- icon -->
<link rel="icon" href="/assets/img/logo/icon.svg" type="image/svg+xml">

<!-- apple -->
<meta name="application-name" lang="<?=$lang?>" content="<?=h($site['name'])?>">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<!-- css -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800;900&display=swap" />
<link rel="stylesheet" href="/assets/pl/fontawesome/css/all.min.css" />

<!-- main css -->
<? foreach ($scss as $i): ?> <link rel="stylesheet" type="text/css" href="/assets/css/<?=$i?>.css?v=<?=$ver?>" /> <? endforeach ?>
<? foreach ($css as $i): ?> <link rel="stylesheet" type="text/css" href="/assets/css/<?=$i?>.css?v=<?=$ver?>" /> <? endforeach ?>

<!-- js -->
<script src="/assets/pl/jquery.min.js"></script>
<script src="/assets/pl/jquery.lazy.min.js"></script>
<script src="/assets/pl/jquery.mask.min.js"></script>
<script src="/assets/js/fun.js?v=<?=$ver?>"></script>
