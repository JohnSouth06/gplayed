function copyShareLink() {
    var copyText = document.getElementById("shareLinkInput");
    copyText.select();
    copyText.setSelectionRange(0, 99999); // Pour mobile
    navigator.clipboard.writeText(copyText.value).then(function() {
        var feedback = document.getElementById("copyFeedback");
        feedback.style.display = "block";
        setTimeout(function(){ feedback.style.display = "none"; }, 3000);
    });
}