function num(ch) {
    var i = 0;
    var ok = true;
    while (i < ch.length && ok) {
        x = ch.charAt(i);
        if (x >= "0" && x <= "9") {
            i++;
        } else {
            ok = false;
        }
    }
    return ok;
}

function alpha(ch) {
    var i = 0;
    var ok = true;
    while (i < ch.length && ok) {
        x = ch.charAt(i).toUpperCase();
        if ((x >= "A" && x <= "Z") || x == " ") {
            i++;
        } else {
            ok = false;
        }
    }
    return ok;
}

function verif1(){
    var name = document.getElementById("name").value;
    var phone = document.getElementById("phone").value;
    var date = document.getElementById("date").value;
    var guests = document.getElementById("guests").selectedIndex;

    var isValid = true;
    
    document.getElementById("nameError").textContent = "";
    document.getElementById("phoneError").textContent = "";
    document.getElementById("dateError").textContent = "";
    document.getElementById("guestsError").textContent = "";
    
    if (name.length >= 20 || name.length < 3 || alpha(name) == false) {
        document.getElementById("nameError").textContent =
            "Nom non valide (entre 3 et 20 caractères, lettres uniquement).";
        isValid = false;
    }
    
    if (phone.length != 8 || num(phone) == false) {
        document.getElementById("phoneError").textContent =
            "Téléphone non valide (exactement 8 chiffres).";
        isValid = false;
    }
    
    var d = new Date();
    var dt = new Date(date);
    if (date === "" || dt < d) {
        document.getElementById("dateError").textContent =
            "Date non valide (doit être une date future).";
        isValid = false;
    }

    if (guests == 0) {
        document.getElementById("guestsError").textContent =
            "Veuillez sélectionner un nombre de personnes.";
        isValid = false;
    }
    
    if(isValid)
        { alert("Félicitations, votre réservation a été effectuée avec succès ");}

    return isValid;
}

function selectItem(item) {
    document.getElementById('selected-item').value = item;
    document.getElementById('reservationForm').classList.add('active');
}


