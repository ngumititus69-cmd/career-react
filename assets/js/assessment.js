"use strict";

const assessmentForm = document.querySelector("#assessment-form");

if (assessmentForm) {
    const steps = Array.from(assessmentForm.querySelectorAll(".assessment-step"));
    const nextButton = assessmentForm.querySelector("#step-next");
    const backButton = assessmentForm.querySelector("#step-back");
    const submitButton = assessmentForm.querySelector("#step-submit");
    const progressBar = assessmentForm.querySelector("#progress-bar");
    const progressPercent = assessmentForm.querySelector("#progress-percent");
    const stepNumber = assessmentForm.querySelector("#step-current");
    const stepLabel = assessmentForm.querySelector("#step-label");
    const errorMessage = assessmentForm.querySelector("#assessment-error");
    const labels = ["Your interests", "Your skills", "Subjects you enjoy", "Your work preferences", "Your ambitions"];
    let activeStep = 0;

    function renderStep() {
        steps.forEach((step, index) => {
            step.classList.toggle("is-active", index === activeStep);
        });

        const progress = Math.round(((activeStep + 1) / steps.length) * 100);
        stepNumber.textContent = String(activeStep + 1);
        stepLabel.textContent = labels[activeStep];
        progressPercent.textContent = `${progress}%`;
        progressBar.style.width = `${progress}%`;
        backButton.hidden = activeStep === 0;
        nextButton.hidden = activeStep === steps.length - 1;
        submitButton.hidden = activeStep !== steps.length - 1;
        errorMessage.textContent = "";
    }

    function hasSelection(stepIndex) {
        return steps[stepIndex].querySelectorAll('input[type="checkbox"]:checked').length > 0;
    }

    nextButton.addEventListener("click", () => {
        if (!hasSelection(activeStep)) {
            errorMessage.textContent = "Choose at least one answer before continuing.";
            return;
        }

        activeStep = Math.min(activeStep + 1, steps.length - 1);
        renderStep();
    });

    backButton.addEventListener("click", () => {
        activeStep = Math.max(activeStep - 1, 0);
        renderStep();
    });

    assessmentForm.addEventListener("submit", (event) => {
        const firstIncomplete = steps.findIndex((step, index) => !hasSelection(index));
        if (firstIncomplete !== -1) {
            event.preventDefault();
            activeStep = firstIncomplete;
            renderStep();
            errorMessage.textContent = "Choose at least one answer in each section to get your personalised matches.";
            return;
        }

        const careerStage = assessmentForm.querySelector('select[name="career_stage"]');
        if (!careerStage || careerStage.value === "") {
            event.preventDefault();
            activeStep = steps.length - 1;
            renderStep();
            errorMessage.textContent = "Choose your current career stage to finish the assessment.";
            careerStage?.focus();
        }
    });

    renderStep();
}

const menuToggle = document.querySelector(".mobile-menu-toggle");
const appNavigation = document.querySelector(".app-nav");

if (menuToggle && appNavigation) {
    menuToggle.addEventListener("click", () => {
        const isOpen = appNavigation.classList.toggle("is-open");
        menuToggle.setAttribute("aria-expanded", String(isOpen));
    });

    appNavigation.addEventListener("click", (event) => {
        if (event.target instanceof Element && event.target.closest("a")) {
            appNavigation.classList.remove("is-open");
            menuToggle.setAttribute("aria-expanded", "false");
        }
    });
}
