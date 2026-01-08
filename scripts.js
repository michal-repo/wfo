async function login_check() {
    let response;
    response = await axios.get(`api/check`).then(response => {
        return true;
    }).catch(error => {
        return false;
    });
    return response;
}

async function register_check() {
    let response;
    response = await axios.get(`api/register`).then(response => {
        return false;
    }).catch(error => {
        return true;
    });
    return response;
}


function set_month_target(year, month, target) {
    axios.post(`api/target/year/${year}/month/${month}/target/${target}`).then(response => {
        return true;
    }).catch(error => {
        return false;
    });
}

function set_working_days(year, month, target) {
    axios.post(`api/working-days/year/${year}/month/${month}/working-days/${target}`).then(response => {
        return true;
    }).catch(error => {
        return false;
    });
}

function set_year_target(year, target) {
    axios.post(`api/target/year/${year}/target/${target}`).then(response => {
        return true;
    }).catch(error => {
        return false;
    });
}


async function generate_commands() {
    let response;
    response = await axios.get(`api/generate-commands`).then(response => {
        txt = "";
        response.data.data.forEach((el) => {
            if (el !== undefined) {
                txt += el + "<br>";
            }
        })
        document.getElementById("generatedCommandsBody").innerHTML = txt;
        return null;
    }).catch(error => {
        return null;
    });
}

async function get_tokens() {
    let response;
    document.getElementById("existing-tokens-list").innerHTML = ""; // Clear existing list
    response = await axios.get(`api/get-tokens`).then(response => {
        response.data.data.forEach((el) => {
            if (el !== undefined) {
                document.getElementById("existing-tokens-list").innerHTML += "<div> ID: " + el.id + ", Name: " + el.token_name + "<button onclick=\"deleteToken(" + el.id + ")\">Delete</button></div>"; // Append to existing list
            }
        })
        return null;
    }).catch(error => {
        return null;
    });
}

async function deleteToken(token_id) {
    let response;
    response = await axios.post(`api/revoke-token`, { token_id: token_id }).then(response => {
        alert("Token deleted successfully. Please refresh the page to see the updated list.");
        get_tokens();
        return true;
    }).catch(error => {
        alert("Error deleting token.");
        return null;
    });
}

async function newToken(element, token_name_input_id) {
    if (document.getElementById(token_name_input_id).value.trim() === "") {
        alert("Please enter a token name.");
        return null;
    }
    let response;
    response = await axios.post(`api/generate-token`, { token_name: document.getElementById(token_name_input_id).value }).then(response => {
        document.getElementById(element).value = response.data.data.token;
        get_tokens();
        return true;
    }).catch(error => {
        return null;
    });
}


async function get_settings() {
    try {
        const resp = await axios.get(`api/get-settings`);
        const data = resp.data && resp.data.data ? resp.data.data : resp.data;
        // normalize days array
        const days = Array.isArray(data.days_to_show) ? data.days_to_show : [];

        let r = {};
        r.days = days;
        r.language = data.language || null;
        return r;
    } catch (error) {
        return null;
    }
}

async function populate_settings_in_modal() {
    try {
        const data = await get_settings();

        const days = data.days || [];

        // uncheck all first
        document.querySelectorAll('#settings-days-to-show input[type=checkbox]').forEach(el => el.checked = false);
        // check those returned
        days.forEach(d => {
            const el = document.querySelector(`#settings-days-to-show input[value="${d}"]`);
            if (el) el.checked = true;
        });

        // set language select
        if (data.language) {
            const langEl = document.getElementById('settings-language');
            if (langEl) langEl.value = data.language;
        }
    } catch (error) {
        return null;
    }
}

async function save_settings() {
    try {
        const checked = Array.from(document.querySelectorAll('#settings-days-to-show input[type=checkbox]:checked'))
            .map(el => el.value);
        const language = document.getElementById('settings-language') ? document.getElementById('settings-language').value : null;

        await axios.post(`api/save-settings`, {
            days_to_show: checked,
            language: language
        });
        // optional: give feedback
        return true;
    } catch (error) {
        return null;
    }
}

function generate_hidden_days(days) {
    if (!days || days.length === 0) {
        return [];
    }
    const predefined_days = { 0: "sun", 1: "mon", 2: "tue", 3: "wed", 4: "thu", 5: "fri", 6: "sat" };
    let hidden_days = [];
    for (const [i, value] of Object.entries(predefined_days)) {
        if (!days.includes(value)) {
            hidden_days.push(parseInt(i));
        }
    };
    return hidden_days;
}

function update_stats(year, month) {
    const month_target = document.getElementById('month-target');
    const month_target_progressbar = document.getElementById('month-target-progressbar');
    const year_target = document.getElementById('year-target');
    const year_target_progressbar = document.getElementById('year-target-progressbar');
    const working_days = document.getElementById('working-days');
    const holidays = document.getElementById('holidays');
    const sickleave = document.getElementById('sickleave');
    const overtime = document.getElementById('overtime');
    const office_min = document.getElementById('office-actual-min');

    const month_target_edit = document.getElementById('month-target-edit');
    const year_target_edit = document.getElementById('year-target-edit');
    const working_days_edit = document.getElementById('working-days-edit');

    let calc = 0;
    let calc_year = 0;
    axios.get(`api/target/year/${year}/month/${month}`).then(response => {
        month_target.innerText = response.data.data.month_target !== null ? response.data.data.month_target : "100";
        month_target_edit.value = month_target.innerText;
        year_target.innerText = response.data.data.year_target !== null ? response.data.data.year_target : "100";
        year_target_edit.value = year_target.innerText;
        working_days.innerText = response.data.data.working_days !== null ? response.data.data.working_days : "-";
        working_days_edit.value = working_days.innerText;
        holidays.innerText = response.data.data.holidays !== null ? response.data.data.holidays : "-";
        sickleave.innerText = response.data.data.sickleave !== null ? response.data.data.sickleave : "-";
        overtime.innerText = response.data.data.overtime !== null ? response.data.data.overtime + "h" : "-";
        if (response.data.data.working_days !== null
            && response.data.data.office_days !== null
            && response.data.data.overtime_office_only !== null
            && response.data.data.month_target !== null) {

            calc = ((response.data.data.office_days + (response.data.data.overtime_office_only / 8)) / (((response.data.data.working_days - (response.data.data.holidays + response.data.data.sickleave)) * response.data.data.month_target) / 100)) * 100;
            office_min.innerText = (response.data.data.office_days + (response.data.data.overtime_office_only / 8)) + "/" + (((response.data.data.working_days - (response.data.data.holidays + response.data.data.sickleave)) * response.data.data.month_target) / 100);
        }

        if (response.data.data.working_days_year !== null
            && response.data.data.office_days_year !== null
            && response.data.data.overtime_year_office_only !== null
            && response.data.data.year_target !== null) {

            calc_year = ((response.data.data.office_days_year + (response.data.data.overtime_year_office_only / 8)) / (((response.data.data.working_days_year - (response.data.data.holidays_year + response.data.data.sickleave_year)) * response.data.data.year_target) / 100)) * 100;
        }

        if (calc >= 100) {
            month_target_progressbar.classList.add("bg-success");
        } else {
            month_target_progressbar.classList.remove("bg-success");
        }
        if (calc >= 0 && calc <= 50) {
            month_target_progressbar.classList.add("bg-warning");
        } else {
            month_target_progressbar.classList.remove("bg-warning");
        }
        month_target_progressbar.style.width = `${calc}%`;

        if (calc_year >= 100) {
            year_target_progressbar.classList.add("bg-success");
        } else {
            year_target_progressbar.classList.remove("bg-success");
        }
        if (calc_year >= 0 && calc_year <= 50) {
            year_target_progressbar.classList.add("bg-warning");
        } else {
            year_target_progressbar.classList.remove("bg-warning");
        }
        year_target_progressbar.style.width = `${calc_year}%`;

    }).catch(error => {
        month_target.innerText = "No data";
    });
}

