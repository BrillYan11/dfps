console.log("[LocationLoader] Script loaded");

async function loadSelect(url, selectEl, placeholder) {
  if (!selectEl) {
    console.error(`[LocationLoader] Select element for ${placeholder} not found`);
    return;
  }
  
  // The <base> tag in universal_header.php handles relative URL resolution correctly.
  const absoluteUrl = url;

  console.log(`[LocationLoader] Loading ${placeholder} from: ${absoluteUrl}`);
  selectEl.innerHTML = `<option value="">Loading...</option>`;
  
  try {
    const res = await fetch(absoluteUrl);
    console.log(`[LocationLoader] Fetch response status for ${placeholder}: ${res.status}`);
    
    if (!res.ok) {
      const errorText = await res.text();
      console.error(`[LocationLoader] HTTP error! status: ${res.status} for ${absoluteUrl}. Response: ${errorText.substring(0, 200)}`);
      throw new Error(`HTTP error! status: ${res.status}`);
    }
    
    const text = await res.text();
    console.log(`[LocationLoader] Raw response for ${placeholder}: ${text.substring(0, 100)}...`);
    
    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        console.error(`[LocationLoader] JSON parse error for ${placeholder}:`, e);
        console.error(`[LocationLoader] Content that failed to parse:`, text);
        selectEl.innerHTML = `<option value="">Error parsing data</option>`;
        return;
    }

    selectEl.innerHTML = `<option value="">${placeholder}</option>`;
    
    if (data.error) { 
      console.error("[LocationLoader] API Error:", data.error);
      selectEl.innerHTML = `<option value="">Error: ${data.error}</option>`;
      return; 
    }

    if (!Array.isArray(data)) {
      console.error("[LocationLoader] Expected array but got:", data);
      selectEl.innerHTML = `<option value="">Invalid data format</option>`;
      return;
    }

    data.forEach(item => {
      const opt = document.createElement("option");
      opt.value = item.code;
      opt.textContent = item.name;
      selectEl.appendChild(opt);
    });
    console.log(`[LocationLoader] Successfully loaded ${data.length} items for ${placeholder}`);
  } catch (error) {
    console.error(`[LocationLoader] Error loading ${placeholder}:`, error);
    selectEl.innerHTML = `<option value="">Failed to load data</option>`;
  }
}

document.addEventListener("DOMContentLoaded", () => {
  console.log("[LocationLoader] DOMContentLoaded event fired");
  const regionEl = document.getElementById("region");
  const provEl = document.getElementById("province");
  const cityEl = document.getElementById("city");
  const brgyEl = document.getElementById("barangay");
  const cityNameInput = document.getElementById("city_name");
  const brgyNameInput = document.getElementById("barangay_name");

  console.log("[LocationLoader] Elements found:", {
      region: !!regionEl,
      province: !!provEl,
      city: !!cityEl,
      barangay: !!brgyEl
  });

  if (regionEl) {
    loadSelect("includes/locations_api.php?action=regions", regionEl, "Select region");

    regionEl.addEventListener("change", async () => {
      const regionCode = regionEl.value;
      console.log("[LocationLoader] Region changed to:", regionCode);
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
