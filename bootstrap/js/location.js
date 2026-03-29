async function loadSelect(url, selectEl, placeholder) {
  if (!selectEl) return;
  console.log(`Loading ${placeholder} from: ${url}`);
  selectEl.innerHTML = `<option value="">Loading...</option>`;
  
  try {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
    
    const data = await res.json();
    selectEl.innerHTML = `<option value="">${placeholder}</option>`;
    
    if (data.error) { 
      console.error("API Error:", data.error);
      selectEl.innerHTML = `<option value="">Error loading data</option>`;
      return; 
    }

    if (!Array.isArray(data)) {
      console.error("Expected array but got:", data);
      return;
    }

    data.forEach(item => {
      const opt = document.createElement("option");
      opt.value = item.code;
      opt.textContent = item.name;
      selectEl.appendChild(opt);
    });
    console.log(`Successfully loaded ${data.length} items for ${placeholder}`);
  } catch (error) {
    console.error(`Error loading ${placeholder}:`, error);
    selectEl.innerHTML = `<option value="">Failed to load</option>`;
  }
}

document.addEventListener("DOMContentLoaded", () => {
  console.log("Location loader initialized");
  const regionEl = document.getElementById("region");
  const provEl = document.getElementById("province");
  const cityEl = document.getElementById("city");
  const brgyEl = document.getElementById("barangay");
  const cityNameInput = document.getElementById("city_name");
  const brgyNameInput = document.getElementById("barangay_name");

  if (regionEl) {
    loadSelect("includes/locations_api.php?action=regions", regionEl, "Select region");

    regionEl.addEventListener("change", async () => {
      const regionCode = regionEl.value;
      console.log("Region changed to:", regionCode);
      if (provEl) {
        provEl.disabled = false;
        if (cityEl) {
          cityEl.disabled = true;
          cityEl.innerHTML = `<option value="">Select city/municipality</option>`;
          if (brgyEl) {
            brgyEl.disabled = true;
            brgyEl.innerHTML = `<option value="">Select barangay</option>`;
          }
        }
        await loadSelect(`includes/locations_api.php?action=provinces&region_id=${encodeURIComponent(regionCode)}`, provEl, "Select province");
      }
    });
  }

  if (provEl) {
    provEl.addEventListener("change", async () => {
      const provCode = provEl.value;
      console.log("Province changed to:", provCode);
      if (cityEl) {
        cityEl.disabled = false;
        if (brgyEl) {
          brgyEl.disabled = true;
          brgyEl.innerHTML = `<option value="">Select barangay</option>`;
        }
        await loadSelect(`includes/locations_api.php?action=cities&province_id=${encodeURIComponent(provCode)}`, cityEl, "Select city/municipality");
      }
    });
  }

  if (cityEl) {
    cityEl.addEventListener("change", async () => {
      const cityCode = cityEl.value;
      const selectedCityText = cityEl.options[cityEl.selectedIndex].text;
      console.log("City changed to:", cityCode, selectedCityText);
      
      if (cityNameInput) cityNameInput.value = (cityCode !== "") ? selectedCityText : "";
      
      if (brgyEl) {
        if (cityCode !== "") {
          brgyEl.disabled = false; // Enable immediately so it's clickable
          await loadSelect(`includes/locations_api.php?action=barangays&city_id=${encodeURIComponent(cityCode)}`, brgyEl, "Select barangay");
        } else {
          brgyEl.disabled = true;
          brgyEl.innerHTML = `<option value="">Select barangay</option>`;
          if (brgyNameInput) brgyNameInput.value = "";
        }
      }
    });
  }

  if (brgyEl) {
    brgyEl.addEventListener("change", () => {
      const brgyCode = brgyEl.value;
      const selectedBrgyText = brgyEl.options[brgyEl.selectedIndex].text;
      console.log("Barangay changed to:", brgyCode, selectedBrgyText);
      if (brgyNameInput) brgyNameInput.value = (brgyCode !== "") ? selectedBrgyText : "";
    });
  }
});
