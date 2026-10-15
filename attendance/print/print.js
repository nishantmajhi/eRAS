document.addEventListener("DOMContentLoaded", function () {    
    window.addEventListener("beforeprint", removeUtils);
    window.addEventListener("afterprint", restoreUtils);
    
    document.querySelector("button").addEventListener("click", function () {
        window.print();
    });
});

function removeUtils() {
    document.querySelector("aside").style.display = "none";
    document.querySelector("footer").style.display = "none";
}

function restoreUtils() {
    document.querySelector("aside").style.display = "block";
    document.querySelector("footer").style.display = "block";
}