<?php

$coffeeNotes = [
    ["content" => "傳說中，衣索比亞牧羊人發現山羊吃了咖啡果實後異常興奮，這也成為最著名的咖啡起源故事。"],
    ["content" => "麝香貓咖啡來自經過麝香貓消化道的咖啡種子，因稀有而價格高昂，但也存在動物福利爭議。"],
    ["content" => "咖啡香氣包含數百種揮發性化合物，花香、果香、堅果香與巧克力香，都是不同成分共同作用的結果。"],
    ["content" => "義大利那不勒斯有一種 Caffè Sospeso 的懸掛咖啡文化，客人可以預付一杯咖啡，留給有需要的人。"],
    ["content" => "咖啡樹在適當環境下可以存活數十年，有些甚至超過50年，但商業栽培通常會考慮產量與樹齡。"],
    ["content" => "許多咖啡產區位於火山地帶，如哥斯大黎加。火山土壤的礦物組成與排水特性，是影響栽培環境的因素之一。"],
    ["content" => "傳統美式通常先倒濃縮再加水，而Long Black則先加水再倒濃縮，讓咖啡的 Crema 保留得更完整。"],
    ["content" => "Macchiato 在義大利文有染上斑點的意思。傳統Macchiato只在濃縮上加一點奶泡，像在表面留下白色斑點。"],
    ["content" => "Affogato 在義大利文中有「淹沒」的意思。把熱濃縮咖啡淋在冰淇淋上，就像讓冰淇淋被咖啡淹沒！"],
    ["content" => "土耳其人喝咖啡通常不會過濾咖啡粉。喝完後，有些人會把杯子倒扣，根據咖啡渣留下的圖案占卜。"]
];

// 根據今天日期，選出固定的一則咖啡筆記
$today = date("Y-m-d");
$noteIndex = crc32($today) % count($coffeeNotes);
$todayNote = $coffeeNotes[$noteIndex];

?>

<section class="coffee-note-card">
    <h2>☕ 今日咖啡筆記</h2>

    <p><?= htmlspecialchars($todayNote["content"], ENT_QUOTES, "UTF-8") ?></p>
</section>