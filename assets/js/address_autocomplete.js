// =========================
// Address Autocomplete
// =========================
const url = "https://addressr.p.rapidapi.com/addresses?q=";
const headers = {
  // "x-rapidapi-key": "fdb9e0567dmsha6e1dfa8a5d4f52p1f769bjsn932503b8f8e9",
  "x-rapidapi-key": "ce4edd7875msh249ce6e77ef0d06p17bc4fjsn33a5fb6efe20",
  "x-rapidapi-host": "addressr.p.rapidapi.com",
};
let checkoutSection = document.getElementById("checkout-section");

async function searchAddress(query) {
  const options = {
    method: "GET",
    headers: headers,
  };
  try {
    const response = await fetch(`${url}${encodeURIComponent(query)}`, options);
    const result = await response.json();
    console.log(result);
    renderSuggestions(result);
  } catch (error) {
    console.error(error);
  }
}

function enableKeyboardNavigation() {
  const suggestions = document.getElementById("suggestions");
  const input = document.getElementById("autocomplete-address");

  let selectedIndex = -1;

  input.addEventListener("keydown", function (e) {
    const items = suggestions.querySelectorAll("li");
    if (items.length === 0) return;

    if (e.key === "ArrowDown") {
      e.preventDefault();
      selectedIndex = (selectedIndex + 1) % items.length;
      updateHighlight(items);
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      selectedIndex = (selectedIndex - 1 + items.length) % items.length;
      updateHighlight(items);
    } else if (e.key === "Enter") {
      e.preventDefault();
      if (selectedIndex >= 0 && selectedIndex < items.length) {
        input.value = items[selectedIndex].textContent;
        suggestions.innerHTML = "";
        selectedIndex = -1;
      }
    }
  });

  function updateHighlight(items) {
    items.forEach((item, index) => {
      item.classList.toggle("active", index === selectedIndex);
    });
  }
}

async function renderSuggestions(data) {
  const list = document.getElementById("suggestions");
  list.innerHTML = "";
  if (data && Array.isArray(data) && data.length > 0) {
    data.forEach((address) => {
      console.log("Show me ", address);
      const li = document.createElement("li");
      li.textContent = address.sla;
      li.className = "list-group-item list-group-item-action mx";
      li.style.cursor = "pointer";
      li.addEventListener("click", () => {
        const input = document.getElementById("autocomplete-address");
        const idHidden = document.getElementById("address-id");
        if (input) {
          input.value = address.sla;
          idHidden.value = address.pid;
          if (checkoutSection) {
            lookAddressDetailsById(address.pid);
          }
        }
        console.log("INPUT", input.value, idHidden.value);

        list.innerHTML = ""; // Clear suggestions
      });
      list.appendChild(li);
    });
  }
}

function attachAutocomplete() {
  checkoutSection = document.getElementById("checkout-section");
  const autocomplete = document.getElementById("autocomplete-address");
  const suggestions = document.getElementById("suggestions");

  if (!autocomplete || !suggestions) {
    console.warn("⚠️ Input with ID 'autocomplete-address' not found!");
    return;
  }

  autocomplete.addEventListener("input", function () {
    const input = this.value.trim();
    if (input.length >= 3) {
      searchAddress(input);
    } else {
      suggestions.innerHTML = "";
    }
  });

  document.addEventListener("click", function (event) {
    if (!event.target.closest("#autocomplete-address")) {
      suggestions.innerHTML = "";
    }
  });
}

async function lookAddressDetailsById(addressId) {
  const street = document.getElementById("street");
  const apartment = document.getElementById("apartment");
  const city = document.getElementById("city");
  const zipcode = document.getElementById("zipcode");

  console.log("Address", addressId);
  try {
    await fetch(`/address?address=${addressId}`, {
      method: "GET",
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        console.log("DATA", data);

        if (data.success) {
          let addressData = data.address;
          street.value =
            addressData["streetNumberFrom"] +
            ((!addressData["streetNumberTo"] ? "" : " - ") +
              (addressData["streetNumberTo"] === null
                ? ""
                : addressData["streetNumberTo"])) +
            ` ${addressData["streetName"]} ${addressData["streetType"]} ${addressData["suburb"]}`;
          apartment.value = `${addressData["typeApt"]} ${addressData["unitNumber"]}`;
          city.value = addressData.state;
          zipcode.value = addressData["postcode"];
        }
      })
      .catch((error) => {
        console.error("Fetch error:", error.response);
        showToast({
          title: "Network Error",
          message: "Could not connect to server.",
          type: "danger",
          image: "assets/images/icons/error.png",
        });
      });
  } catch (error) {
    console.error(error);
  }
}

if (checkoutSection) {
  attachAutocomplete();
}

