<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebView</title>
</head>
<body>
    <script>
         document.body.style.padding = "0px";
            var classNames = ["n-header", "result-header", "resultHeader", "adsbygoogle", "FDTitleText", "FDTitleText2", "Disclaimer"];
            classNames.forEach(function(className) {
                var elements = document.querySelectorAll("." + className);
                elements.forEach(function(element) {
                    element.style.display = "none";
                });
            });
            var taboolaElement = document.getElementById("taboola-below-article-thumbnails");
            if (taboolaElement) {
                taboolaElement.style.display = "none";
            }
    </script>
    <iframe src="https://lottery.nestia.com/sweep" width="100%" height="1000px"></iframe>
</body>
</html>
