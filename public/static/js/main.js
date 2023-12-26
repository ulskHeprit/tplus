$(document).ready(function() {
    $thermalUnitSelect = $('#thermal_units');
    $departmentSelect = $('#departments');

    if (!$thermalUnitSelect || !$departmentSelect) {
        return;
    }

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
});
