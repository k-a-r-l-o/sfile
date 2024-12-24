// add hovered class to selected list item
let list = document.querySelectorAll(".navigation li");

function activeLink() {
  list.forEach((item) => {
    item.classList.remove("hovered");
  });
  this.classList.add("hovered");
}

list.forEach((item) => item.addEventListener("mouseover", activeLink));

// Menu Toggle
let toggle = document.querySelector(".toggle");
let navigation = document.querySelector(".navigation");
let main = document.querySelector(".main");

toggle.onclick = function () {
  navigation.classList.toggle("active");
  main.classList.toggle("active");
};

// Ensure the navigation remains collapsed when the active class is set
document.addEventListener("DOMContentLoaded", function() {
  const menuToggleButton = document.querySelector('.menu-toggle');  // Make sure this matches the button in your HTML
  const tabs = document.querySelectorAll('.navigation ul li a');
  
  // Toggle collapse state when clicking on the menu button
  menuToggleButton.addEventListener('click', function() {
    navigation.classList.toggle('active');
    main.classList.toggle('active');
  });

  // Prevent the navigation from expanding when a tab is clicked
  tabs.forEach(tab => {
    tab.addEventListener('click', function(event) {
      if (navigation.classList.contains('active')) {
        // Keep the navigation collapsed if it's already in the collapsed state
        event.stopPropagation();
      }
    });
  });
});

