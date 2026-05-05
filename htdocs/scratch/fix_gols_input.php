<?php
$files = [
    'd:/laragon/www/unseraz/htdocs/input_dosen.php'
];

$gols_html = '<option value="III/a">III/a</option><option value="III/b">III/b</option>
                                                                 <option value="III/c">III/c</option><option value="III/d">III/d</option>
                                                                 <option value="IV/a">IV/a</option><option value="IV/b">IV/b</option>
                                                                 <option value="IV/c">IV/c</option><option value="IV/d">IV/d</option>';

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Fix DIKTI dropdown
    $pattern_dikti = '/(<select name="gol_lldikti\[\]" class="form-select form-select-sm">.*?<option value="">- Pilih -<\/option>).*?(<\/select>)/s';
    $content = preg_replace($pattern_dikti, '$1' . "\n" . '                                                                 ' . $gols_html . "\n" . '                                                             $2', $content);
    
    // Fix Yayasan dropdown (if not already fully fixed)
    $pattern_yayasan = '/(<select name="gol_yayasan\[\]" class="form-select form-select-sm">.*?<option value="">- Pilih -<\/option>).*?(<\/select>)/s';
    $content = preg_replace($pattern_yayasan, '$1' . "\n" . '                                                                 ' . $gols_html . "\n" . '                                                             $2', $content);

    file_put_contents($file, $content);
    echo "Updated $file\n";
}
?>
