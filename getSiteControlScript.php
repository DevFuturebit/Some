<script>
    (function (w,i,d,g,e,t,s) {w[d] = w[d]||[];t= i.createElement(g);
        t.async=1;t.src=e;s=i.getElementsByTagName(g)[0];s.parentNode.insertBefore(t, s);
    })(window, document, '_gscq','script','//widgets.getsitecontrol.com/57984/script.js');

    _gscq.push(['callback','submit', function (widgetId, data) {
        if (data.form && data.form.email && data.form.email.length) {
            $.ajax({
                url: 'https://www.lensmaster.ru/local/ajax/sendsaysendform.php?email='+data.form.email[0].value,
                dataType: "jsonp",
                success: function (data) {
                    //console.log(data)
                }
            });
        }
    }]);
</script>