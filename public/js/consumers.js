$(document).ready(function () {

    $('#btnImportConsumers').on('click', function () {
        $('#importConsumersForm')[0].reset();
        $('.progress').hide();
        $('.progress-bar').css('width', '0%').text('0%');
        $('#importStatus').text('');
        $('#importSummary').addClass('d-none');
        $('#cancelImport').prop('disabled', false);

        $('#importConsumersModal').modal({
            backdrop: 'static',
            keyboard: false
        }).modal('show');
    });

    $('#cancelImport').on('click', function () {
        $('#importLogContent').text('');
        $('#importConsumersModal').modal({
            backdrop: 'static',
            keyboard: false
        }).modal('hide');
    });

    $('#importConsumersForm').on('submit', function (e) {
        e.preventDefault();

        var formData = new FormData(this);
        $('.progress').show();
        $('.progress-bar').css('width', '0%').text('0%');
        $('#importStatus').text('Uploading file...');
        $('#cancelImport').prop('disabled', true);

        $.ajax({
            url: `${baseUrl}/consumers/import`,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function () {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function (evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        $('.progress-bar').css('width', percentComplete + '%').text(percentComplete + '%');
                        if (percentComplete === 100) {
                            $('#importStatus').text('Processing file...');
                        }
                    }
                }, false);
                return xhr;
            },
            success: function (response) {
                if (response.tracking_id) {
                    $('#importStatus').text('Processing in background...');
                    pollSummary(response.tracking_id);
                }
            },
            error: function () {
                $('#importStatus').text('❌ An error occurred during import.');
                enableClosing();
            }
        });
    });

    function pollSummary(trackingId) {
        let attempts = 0;
        let timer = setInterval(function () {
            $.get(`${baseUrl}/consumers/import-summary/${trackingId}`, function (data) {
                if (data.messages && data.messages.length) {
                    $('#importLog').html(data.messages.join('<br>'));
                }

                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(timer);

                    if (data.status === 'completed') {
                        $('#totalImported').text(data.total_imported);
                        $('#totalBatches').text(data.total_batches);
                        let seconds = parseFloat(data.elapsed_time); 
                        $('#totalTime').text(formatDuration(seconds));
                        $('#importSummary').removeClass('d-none');
                        $('#importStatus').text('✅ Import completed.');
                    } else {
                        $('#importStatus').text('❌ ' + data.error);
                    }
                    enableClosing();
                }
            });
            attempts++;
            if (attempts > 60) { // stop after ~5 minutes
                clearInterval(timer);
                $('#importStatus').text('Timed out waiting for job.');
                enableClosing();
            }
        }, 5000);
    }

    function enableClosing() {
        $('#cancelImport').prop('disabled', false);
    }

    function formatDuration(seconds) {
        seconds = Math.floor(seconds);
        if (seconds < 60) {
            return seconds + 's';
        }
        let minutes = Math.floor(seconds / 60);
        if (minutes < 60) {
            return minutes + 'm ' + (seconds % 60) + 's';
        }
        let hours = Math.floor(minutes / 60);
        if (hours < 24) {
            return hours + 'h ' + (minutes % 60) + 'm';
        }
        let days = Math.floor(hours / 24);
        return days + 'd ' + (hours % 24) + 'h';
    }

});
