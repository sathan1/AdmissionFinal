function updateDepartments() {
    var department1 = document.getElementById("department1").value;
    var department2 = document.getElementById("department2").value;

    // Reset all options to be enabled before applying new logic
    var department2Options = document.getElementById("department2").options;
    for (var i = 0; i < department2Options.length; i++) {
        department2Options[i].disabled = false;
        department2Options[i].classList.remove("faded");
    }

    // Disable the department selected in department 1
    if (department1) {
        for (var i = 0; i < department2Options.length; i++) {
            if (department2Options[i].value === department1) {
                department2Options[i].disabled = true;
                department2Options[i].classList.add("faded");
            }
        }
    }

    // Disable the department selected in department 2 (if already selected)
    if (department2) {
        var department1Options = document.getElementById("department1").options;
        for (var i = 0; i < department1Options.length; i++) {
            if (department1Options[i].value === department2) {
                department1Options[i].disabled = true;
                department1Options[i].classList.add("faded");
            }
        }
    }

    // Prevent submission if both preferences are the same
    var submitButton = document.querySelector('button[type="submit"]');
    if (department1 && department2 && department1 === department2) {
        submitButton.disabled = true;
        alert("Department preferences must be different.");
    } else {
        submitButton.disabled = false;
    }
}

// Attach the function to `department1` and `department2` onchange events
document.getElementById("department1").addEventListener("change", updateDepartments);
document.getElementById("department2").addEventListener("change", updateDepartments);

// Call the function once when the page loads to ensure it's in the correct state
window.onload = updateDepartments;