"use strict";

const menuButton = document.querySelector(".navbar-toggler");
const navigation = document.querySelector("#primary-navigation");

if (menuButton && navigation) {
    menuButton.addEventListener("click", () => {
        const isOpen = navigation.classList.toggle("is-open");
        menuButton.setAttribute("aria-expanded", String(isOpen));
    });

    navigation.addEventListener("click", (event) => {
        if (event.target instanceof HTMLAnchorElement && navigation.classList.contains("is-open")) {
            navigation.classList.remove("is-open");
            menuButton.setAttribute("aria-expanded", "false");
        }
    });
}

const yearElement = document.querySelector("#current-year");
if (yearElement) {
    yearElement.textContent = String(new Date().getFullYear());
}
