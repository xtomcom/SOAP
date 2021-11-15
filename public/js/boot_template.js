const fetchBootTemplate = function () {
    const tplSelect = document.getElementById('Host_bootTemplate');
    const ipxeInput = document.getElementById('Host_ipxeScript');
    const preseedInput = document.getElementById('Host_preseed');
    tplSelect.addEventListener('change', function (e) {
        const request = new XMLHttpRequest();
        request.open('GET', '/api/v1/bootTemplate/' + tplSelect.value, true);

        request.onload = function() {
            if (this.status === 200) {
                // Success!
                const data = JSON.parse(this.response);
                ipxeInput.nextSibling.CodeMirror.setValue(data.ipxeScript);
                preseedInput.nextSibling.CodeMirror.setValue(data.preseed);
            }
        };

        request.send();
    });
};

if (document.readyState !== 'loading') {
    fetchBootTemplate();
} else {
    document.addEventListener('DOMContentLoaded', fetchBootTemplate);
}
