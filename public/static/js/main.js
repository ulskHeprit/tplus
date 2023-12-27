$(document).ready(function() {
    $thermalUnitSelect = $('#thermal_units');
    $departmentSelect = $('#departments');

    if ($thermalUnitSelect && $departmentSelect) {
        $departmentSelect.on('change', function() {
            let department_id = this.value;
            $.ajax({
                url: '/api/thermal_units',
                method: 'get',
                dataType: 'html',
                data: {department_id: department_id},
                success: function(jsonString){
                    let termal_units = $.parseJSON(jsonString);
                    $thermalUnitSelect.empty();

                    termal_units.forEach(function (termal_unit) {
                        let option = document.createElement('option');
                        option.setAttribute('value', termal_unit.id);
                        option.innerText = termal_unit.name;
                        $thermalUnitSelect.append(option);
                    });
                },
                error: function() {
                    console.log('Не удалось получить тепловые узлы')
                }
            });
        });
    }

    $longitudeInput = $('#longitude');
    $latitudeInput = $('#latitude');

    if ($longitudeInput && $latitudeInput) {
        const map = new ol.Map({
            layers: [
                new ol.layer.Tile({
                    source: new ol.source.OSM(),
                }),
            ],
            target: 'map',
            view: new ol.View({
                center: [0, 0],
                zoom: 1,
            }),
        });

        if ($latitudeInput.val() && $longitudeInput.val()) {
            let point = new ol.geom.Point(ol.proj.transform([$longitudeInput.val(), $latitudeInput.val()], 'EPSG:4326', 'EPSG:3857'));
            let feature = new ol.Feature({
                geometry: point
            });
            let vectorLayer = new ol.layer.Vector({
                source: new ol.source.Vector({
                    features: [feature]
                })
            });
            map.addLayer(vectorLayer);
        }

        map.on('click', function(e) {
            let [longitude, latitude] = ol.proj.transform(e.coordinate, 'EPSG:3857', 'EPSG:4326');

            $longitudeInput.val(longitude);
            $latitudeInput.val(latitude);
        });
    }
});
