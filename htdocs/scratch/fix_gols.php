<?php
$files = [
    'd:/laragon/www/unseraz/htdocs/input_dosen.php',
    'd:/laragon/www/unseraz/htdocs/form_edit_dosen.php'
];

// Define options with actual newlines
$gols_html = '<option value="III/a">III/a</option><option value="III/b">III/b</option>
                                                                 <option value="III/c">III/c</option><option value="III/d">III/d</option>
                                                                 <option value="IV/a">IV/a</option><option value="IV/b">IV/b</option>
                                                                 <option value="IV/c">IV/c</option><option value="IV/d">IV/d</option>';

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // For input_dosen.php: fix Yayasan dropdown
    if (basename($file) == 'input_dosen.php') {
        // First, clean up the literal \n if they were inserted
        $content = str_replace('\n', "\n", $content);
        
        // Re-apply correct replacement
        $pattern = '/(<select name="gol_yayasan\[\]" class="form-select form-select-sm">.*?<option value="">- Pilih -<\/option>).*?(<\/select>)/s';
        $replacement = '$1' . "\n" . '                                                                 ' . $gols_html . "\n" . '                                                             $2';
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // For form_edit_dosen.php: move definition and fix loops
    if (basename($file) == 'form_edit_dosen.php') {
        // Remove fragmented definition if it exists
        $content = preg_replace('/<\?php \$gols = \s*\[.*?\]; foreach\(\$gols as \$g\): \?>/s', '<?php foreach($gols_dosen as $g): ?>', $content);
        
        // Ensure $gols_dosen is defined at the top of the history section
        if (strpos($content, '$gols_dosen') === false) {
             $content = preg_replace('/(\$serdoses\s*=\s*.*?DESC"\)\) \? \$q->fetch_all\(MYSQLI_ASSOC\) : \[\];)/', "$1\n" . '$gols_dosen          = [\'III/a\',\'III/b\',\'III/c\',\'III/d\',\'IV/a\',\'IV/b\',\'IV/c\',\'IV/d\'];', $content);
        }
        
        // Update any remaining foreach($gols as $g) to foreach($gols_dosen as $g)
        $content = str_replace('foreach($gols as $g)', 'foreach($gols_dosen as $g)', $content);
    }
    
    file_put_contents($file, $content);
    echo "Updated $file\n";
}
?>
