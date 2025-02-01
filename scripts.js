async function login_check() {
    let response;
    response = await axios.get(`/wfo/api/check`).then(response => {
        return true;
    }).catch(error => {
        return false;
    });
    return response;
}

async function register_check() {
    let response;
    response = await axios.get(`/wfo/api/register`).then(response => {
        return false;
    }).catch(error => {
        return true;
    });
    return response;
}


function set_month_target(year, month, target) {
    axios.post(`/wfo/api/target/year/${year}/month/${month}/target/${target}`).then(response => {
        return true;
    }).catch(error => {
        return false;
    });
}

function set_working_days(year, month, target) {
    axios.post(`/wfo/api/working-days/year/${year}/month/${month}/working-days/${target}`).then(response => {
        return true;
    }).catch(error => {
        return false;
    });
}

function set_year_target(year, target) {
    axios.post(`/wfo/api/target/year/${year}/target/${target}`).then(response => {
        return true;
    }).catch(error => {
        return false;
    });
}

function update_stats(year, month) {
    const month_target = document.getElementById('month-target');
    const month_target_progressbar = document.getElementById('month-target-progressbar');
    const year_target = document.getElementById('year-target');
    const year_target_progressbar = document.getElementById('year-target-progressbar');
    const working_days = document.getElementById('working-days');
    const holidays = document.getElementById('holidays');
    const office_min = document.getElementById('office-min');

    const month_target_edit = document.getElementById('month-target-edit');
    const year_target_edit = document.getElementById('year-target-edit');
    const working_days_edit = document.getElementById('working-days-edit');

    let calc = 0;
    let calc_year = 0;
    axios.get(`/wfo/api/target/year/${year}/month/${month}`).then(response => {
        month_target.innerText = response.data.data.month_target !== null ? response.data.data.month_target : "100";
        month_target_edit.value = month_target.innerText;
        year_target.innerText = response.data.data.year_target !== null ? response.data.data.year_target : "100";
        year_target_edit.value = year_target.innerText;
        working_days.innerText = response.data.data.working_days !== null ? response.data.data.working_days : "-";
        working_days_edit.value = working_days.innerText;
        holidays.innerText = response.data.data.holidays !== null ? response.data.data.holidays : "-";
        if (response.data.data.working_days !== null
            && response.data.data.office_days !== null
            && response.data.data.month_target !== null) {

            calc = (response.data.data.office_days / (((response.data.data.working_days - response.data.data.holidays) * response.data.data.month_target) / 100)) * 100;
            office_min.innerText = (((response.data.data.working_days - response.data.data.holidays) * response.data.data.month_target) / 100);
        }

        if (response.data.data.working_days_year !== null
            && response.data.data.office_days_year !== null
            && response.data.data.year_target !== null) {

            calc_year = (response.data.data.office_days_year / (((response.data.data.working_days_year - response.data.data.holidays_year) * response.data.data.year_target) / 100)) * 100;
        }

        if (calc >= 100) {
            month_target_progressbar.classList.add("bg-success");
        } else {
            month_target_progressbar.classList.remove("bg-success");
        }
        month_target_progressbar.style.width = `${calc}%`;

        if (calc_year >= 100) {
            year_target_progressbar.classList.add("bg-success");
        } else {
            year_target_progressbar.classList.remove("bg-success");
        }
        year_target_progressbar.style.width = `${calc_year}%`;

    }).catch(error => {
        month_target.innerText = "No data";
    });
}

