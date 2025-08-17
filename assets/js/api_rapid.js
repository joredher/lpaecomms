const url = "https://addressr.p.rapidapi.com/addresses?q=";
const headers = {
  "x-rapidapi-key": "fdb9e0567dmsha6e1dfa8a5d4f52p1f769bjsn932503b8f8e9",
  "x-rapidapi-host": "addressr.p.rapidapi.com",
};

async function searchAddress(query) {
  const options = {
    method: "GET",
    headers: headers,
  };
  try {
    const response = await fetch(`${url}${encodeURIComponent(query)}`, options);
    const result = await response.json(); // ✅ FIXED: parse JSON
    console.log(result);
    renderSuggestions(result);
  } catch (error) {
    console.error(error);
  }
}

function renderSuggestions(data) {
  const list = document.getElementById("suggestions");
  list.innerHTML = "";
  if (Array.isArray(data) && data.length > 0) {
    data.forEach((address) => {
      const li = document.createElement("li");
      li.textContent = address.fullAddress;
      li.className = "list-group-item list-group-item-action";
      li.style.cursor = "pointer";
      li.addEventListener("click", () => {
        document.getElementById("autocomplete-address").value =
          address.fullAddress;
        list.innerHTML = "";
      });
      list.appendChild(li);
    });
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const autocomplete = document.getElementById("autocomplete-address"); // ✅ FIXED: consistent ID

  if (!autocomplete) {
    console.warn("⚠️ Input with ID 'autocomplete-address' not found!");
    return;
  }

  autocomplete.addEventListener("input", function () {
    const input = this.value;
    if (input.length >= 3) {
      searchAddress(input);
    } else {
      document.getElementById("suggestions").innerHTML = "";
    }
  });
});

// Optional: close suggestions when clicking outside
document.addEventListener("click", function (event) {
  const suggestions = document.getElementById("suggestions");
  if (!event.target.closest("#autocomplete-address")) {
    suggestions.innerHTML = "";
  }
});
