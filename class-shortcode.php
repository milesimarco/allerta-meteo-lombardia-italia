<?php
    
    extract(shortcode_atts(
        array(
            'id' => '0',
            'height' => '',
            'width' => '100%',
            'title' => ''
        ),
        $atts)
    );


    if (!$id) { echo 'NO ID FOUND'; return; }

    amli_scrape();

    $results = get_option( 'amli_'.$id );

    $res = amli_get_alert_display( $results['idrogeologico'] );
    $idro = $res[0];
    $idro_c = $res[1];

    $res = amli_get_alert_display( $results['idraulico'] );
    $idra = $res[0];
    $idra_c = $res[1];

    $res = amli_get_alert_display( $results['temporali'] );
    $tempo = $res[0];
    $tempo_c = $res[1];

    $res = amli_get_alert_display( $results['vento'] );
    $vento = $res[0];
    $vento_c = $res[1];

    echo '
    <table>
    <thead>
        <tr>
            <th>'.amli_get_paesi($id).' (IM-'.$id.')</th>
            <th>Criticità</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th scope="row">Idrogeologico</th>
            <td bgcolor="'.$idro_c.'" style="text-align: center;">'.$idro.'</td>
        </tr>
        <tr>
            <th scope="row">Idraulico</th>
            <td bgcolor="'.$idra_c.'" style="text-align: center;">'.$idra.'</td>
        </tr>
        <tr>
            <th scope="row">Temporali Forti</th>
            <td bgcolor="'.$tempo_c.'" style="text-align: center;">'.$tempo.'</td>
        </tr>
        <tr>
            <th scope="row">Vento</th>
            <td bgcolor="'.$vento_c.'" style="text-align: center;">'.$vento.'</td>
        </tr>
        <tr>
            <td colspan="2">
                <small>Ultimo aggiornamento: '.date('H:i', get_option( 'amli_last_update' )).' - Dati a cura di <a href="https://www.allertalom.regione.lombardia.it/" title="Allerte meteo Regione Lombardia">Regione Lombardia</a></small>
            </td>
        </tr>
    </tbody>
</table>';
?>