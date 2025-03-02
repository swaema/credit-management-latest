// let currentStep = 1;

// function showStep(step) {
//   const steps = document.querySelectorAll('.step');
//   steps.forEach(s => s.classList.remove('active'));
//   document.getElementById(`step-${step}`).classList.add('active');
  
//   updateProgressBar(step);
// }

// function updateProgressBar(step) {
//   const progressSteps = document.querySelectorAll('.step-progress');
//   progressSteps.forEach((step, index) => {
//     step.classList.remove('active', 'completed');
//     if (index < step - 1) {
//       step.classList.add('completed');
//     } else if (index === step - 1) {
//       step.classList.add('active');
//     }
//   });
// }

// function nextStep(step) {
//   if (step <= 3) {
//     currentStep = step;
//     showStep(currentStep);
//   }
// }

// function previousStep(step) {
//   if (step >= 1) {
//     currentStep = step;
//     showStep(currentStep);
//   }
// }

// // Initialize the form with the first step visible
// showStep(currentStep);
