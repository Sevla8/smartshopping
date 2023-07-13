# -*- coding: utf-8 -*-
from selenium import webdriver
from time import sleep
from bs4 import BeautifulSoup
import sys
from decimal import Decimal
import datetime
import encodings

enseignes = {"Marche Bonichoix":9,
            "Bonichoix":9,
            "Super C":7,
            "Inter Marche":22,
            "Intermarche":22,
            "Intermarche (Provincial)":22,
            "Intermarché (Saveurs intl.)":22,
            "Loblaws":3,
            "Walmart":25,
            "Metro":6,
            "Provigo":1,
            "Tradition":8,
            "Richelieu":10,
            "Marche Richelieu":10,
            "Marché Adonis":13,
            "Mourelatos":14,
            "Supermarché PA":15,
            "Jardin Mobile":33,
            "Real Canadian Superstore":31,
            "Safeway":69,
            "Sobeys":30,
            "Coop":66,
            "Extra Foods":54,
            "Save-On-Foods":70,
            "Thrifty":44,
            "Thrifty's":44,
            "PriceSmart":68,
            "Atlantic Superstore":52,
            "Save Easy":57,
            "Foodland":41,
            "Food Land":41,
            "MarketPlaceIGA":2,
            "Dominion":53,
            "SaveEasy":57,
            "Your Independent Grocer":61,
            "Price Chopper":47,
            "FreshCo":50,
            "Fresh Co":50,
            "Farm Boy":65,
            "No Frills":56,
            "Food Basics":67,
            "IGA":2,
            "Coop IGA":2,
            "Maxi":4,
            "Rachelle-Berry":38,
            "Rachelle Berry":38,
            "Rachelle Béry":38,
            "Fruiterie 440":16}
#"IGA":2,

categorie_soscuisine = {  "Fruits":16,
                       "Vegetables":17,
                       "Herbs":15,
                       "Dairy Products":5,
                       "Meat & Poultry":6,
                       "Fish & Seafood":7,
                       "Refrigerated Items":13,
                       "Frozen Foods":8,
                       "Pasta & Rice":9,
                       "Bakery":22,
                       "Baking Products":24,
                       "Spices":18,
                       "Oil & Vinegar":19,
                       "Condiments":20,
                       "Nuts":21,
                       "Canned Food":23,
                       "Dry Good":10,
                       "Gluten Free":26,
                       "Alcohol":11,
                       "Non Food":27}


# categorie = {  "Fruits":16:12,
#                "Vegetables":17:12,
#                "Herbs":15:9,
#                "Dairy Products":5:15,
#                "Meat & Poultry":6:17,
#                "Fish & Seafood":7:11,
#                "Refrigerated Items":13:14,
#                "Frozen Foods":8:1,
#                "Pasta & Rice":9:13,
#                "Bakery":22:4,
#                "Baking Products":24:14,
#                "Spices":18:9,
#                "Oil & Vinegar":19:9,
#                "Condiments":20:9,
#                "Nuts":21:8,
#                "Canned Food":23:8,
#                "Dry Good":10:8,
#                "Gluten Free":26,
#                "Alcohol":11:2,
#                "Non Food":27:24}


categories = {  "16":12,
               "17":12,
               "15":9,
               "5":15,
               "6":17,
               "7":11,
               "13":14,
               "8":1,
               "9":13,
               "22":4,
               "24":14,
               "18":9,
               "19":9,
               "20":9,
               "21":8,
               "23":8,
               "10":8,
               "26":5,
               "11":2,
               "27":24}

month = { "jan" : "January",
        "feb" : "February",
        "mar" : "March",
        "apr" : "April",
        "may" : "May",
        "jun" : "June",
        "jul" : "July",
        "aug" : "August",
        "sep" : "September",
        "sept": "September",
        "oct" : "October",
        "nov" : "November",
        "dec" : "December"}

nb_month = { "01": "January",
             "02": "February",
             "03": "March",
             "04": "April",
             "05": "May",
             "06": "June",
             "07": "July",
             "08": "August",
             "09": "September",
             "10": "October",
             "11": "November",
             "12": "December"}

regions_list = ["CA-AB",
                "CA-BC",
                "CA-MB",
                "CA-NB",
                "CA-NL",
                "CA-NS",
                "CA-ON",
                "CA-PE",
                "CA-QC",
                "CA-SK"]

regions_dic = { "CA-AB":3,
                "CA-BC":2,
                "CA-MB":7,
                "CA-NB":10,
                "CA-NL":13,
                "CA-NS":12,
                "CA-ON":8,
                "CA-PE":14,
                "CA-QC":1,
                "CA-SK":5}

regionsIds_dic = { '3':"CA-AB",
                   '2':"CA-BC",
                   '7':"CA-MB",
                   '10':"CA-NB",
                   '13':"CA-NL",
                   '12':"CA-NS",
                   '8':"CA-ON",
                   '14':"CA-PE",
                   '1':"CA-QC",
                   '5':"CA-SK"}

enseignes_soscuisine = dict()
data_soscuisine = {}
ligne_data_soscuine = [0]*29
id_produit = ""
cpt = 0
id_enseigne = 0
factor = 1
prix_regulier = 0
prix_special = 0

browser = webdriver.PhantomJS(executable_path='/var/www/html/email/phantomjs')
browser.set_window_size(900, 900)
browser.set_window_position(0, 0)
sleep(1)
browser.get("https://www.soscuisine.com/login")
browser.find_element_by_id("username").send_keys("nadia.tahiri@gmail.com")
browser.find_element_by_id("password").send_keys("cddc")
browser.find_element_by_id("_submit").click()
sleep(2)

browser.get("https://www.soscuisine.com/flyer-specials/")
sleep(5)
browser.refresh()
sleep(2)


for key, value in regionsIds_dic.items():
    id_database = key
    region = regionsIds_dic[id_database]
    print(region+" ...")
    for lg in ("en", "fr"):
        if lg == "en":
            print("EN...")
            browser.get("https://www.soscuisine.com/?lang=en&sos_r="+region)
            sleep(2)
            browser.get("https://www.soscuisine.com/flyer-specials/")
            sleep(5)
            browser.find_element_by_xpath("//select[@name='example_length']/option[text()='ALL']").click()
            sleep(15)

            html = browser.page_source
            file_object = open("/tmp/soscuisine/soscuisine_"+ id_database + "_en.html", "w+")
            file_object.write(html)
            file_object.close()

            soup = BeautifulSoup(browser.page_source, 'lxml')
            table = soup.find_all('table')[0]

            table_rows = table.find_all('tr')
            for tr in table_rows:
                # Initialisation des variables pour chaque nouveau produit
                td = tr.find_all('td')
                nb_colonne = 0
                factor = 1
                prix_regulier = 0
                prix_special = 0
                ligne_data_soscuine = [0]*29
                id_produit = 0
                id_produit_id_enseigne_id_database = 0
                for i in td:
                    # Pour connaitre le facteur muticatif du
                    # prix regulier vs prix special
                    if nb_colonne == 0:
                        row = [i]
                        rows = str(row).split("<i class=\"fa fa-thumbs-up\">")
                        if len(rows)==3:
                            factor = 1.3
                        elif len(rows)==2:
                            factor = 1.15
                        else:
                            factor = 1.09
                    # id_produit
                    elif nb_colonne == 1:
                        row = [i]
                        rows = str(row).split("data-soscuisine-sip=\"")
                        categorie = str(rows[0]).split("data-soscuisine-aisle=")[1]
                        categorie = str(categorie).split("\"")[1]
                        if str(categorie) in categories:
                            ligne_data_soscuine[12] = categories[str(categorie)]
                        rows = str(rows[1]).split("\"")
                        row = rows[0]
                        id_produit = row
                    # description du produit
                    elif nb_colonne == 3:
                        row = [i]
                        rows = str(row).split("<td>")
                        if len(rows)>1:
                            rows = str(rows[1]).split("</td>")
                            description = rows[0]
                            ligne_data_soscuine[7] = description
                            ligne_data_soscuine[6] = ""
                            ligne_data_soscuine[9] = "spanish"
                            ligne_data_soscuine[10] = "spanish2"
                    # id_produit, id_enseigne, id_database
                    elif nb_colonne == 5:
                        row = [i]
                        rows = str(row).split("\"")
                        #rows[1] = unicode(rows[1], "utf-8")
                        if rows[1] in enseignes:
                            row = enseignes[rows[1]]
                            id_enseigne = row
                            id_produit_all = str(id_produit) + "_" + str(id_enseigne) + "_" + str(cpt)
                            id_produit_id_enseigne_id_database = str(id_produit) + "_" + str(id_enseigne) + "_" + str(id_database)
                            ligne_data_soscuine[0] = id_produit_all
                            ligne_data_soscuine[1] = id_database
                            ligne_data_soscuine[2] = id_enseigne
                            if rows[1] in enseignes_soscuisine:
                                enseignes_soscuisine[rows[1]] = enseignes_soscuisine[rows[1]] + 1
                            else:
                                enseignes_soscuisine[rows[1]] = 1
                        else:
                            print(rows[1])
                    # Prix_regulier, prix_special, quantite
                    elif nb_colonne == 6:
                        row = [i.text]
                        #row = row[0]
                        rows = str(row).split("/")
                        if len(rows)==1:
                            rows = str(row).split(" ")

                        if len(rows)>1:
                            rows[0] = str(rows[0]).split("$")[1]
                            row = rows
                        prix_special = float(row[0])
                        prix_regulier = prix_special * factor
                        ligne_data_soscuine[3] = prix_regulier
                        ligne_data_soscuine[4] = prix_special

                        #quantite
                        if len(rows)>1:
                            row[1] = str(row[1]).split("\'")[0]
                            row[1] = str(row[1]).split("\"")[0]
                            ligne_data_soscuine[5] = row[1]
                            ligne_data_soscuine[8] = row[1]
                            ligne_data_soscuine[19] = row[1]
                            ligne_data_soscuine[20] = row[1]
                        else:
                            ligne_data_soscuine[5] = ""
                            ligne_data_soscuine[8] = ""
                            ligne_data_soscuine[19] = ""
                            ligne_data_soscuine[20] = ""
                        ligne_data_soscuine[21] = "quantite_es"
                    # date
                    elif nb_colonne == 8:
                        row = [i.text]
                        row = row[0]
                        rows = str(row).split("-")
                        if len(rows)>=3:
                            date = nb_month[rows[1]] + " " + rows[2]
                            ligne_data_soscuine[22] = date
                            today = datetime.date.today()
                            rows = str(today).split("-")
                            today = nb_month[rows[1]] + " " + rows[2]
                            ligne_data_soscuine[23] = today
                    # brand
                    elif nb_colonne == 10:
                        row = [i.text]
                        row = row[0]
                        ligne_data_soscuine[16] = row
                        ligne_data_soscuine[17] = row
                        ligne_data_soscuine[18] = "brand_es"
                    else:
                        row = [i.text]
                        #row = row[0]
                        ligne_data_soscuine[11] = ""
                        ligne_data_soscuine[13] = "ch"
                        ligne_data_soscuine[14] = "ch"
                        ligne_data_soscuine[15] = "unite_es"
                        ligne_data_soscuine[24] = 1
                        ligne_data_soscuine[25] = 0
                        ligne_data_soscuine[26] = "ligne_fr"
                        ligne_data_soscuine[27] = "ligne_en"
                        ligne_data_soscuine[28] = "supermarches.pl"
                    #ligne_data_soscuine[nb_colonne] = row
                    nb_colonne = nb_colonne + 1

                # Add element of product
                if id_produit_id_enseigne_id_database in data_soscuisine:
                    #data_soscuisine[id_produit+cpt] = ligne_data_soscuine
                    print(id_produit_id_enseigne_id_database)
                else:
                    data_soscuisine[id_produit_id_enseigne_id_database] = ligne_data_soscuine
                cpt = cpt + 1
            # print(len(data_soscuisine))
        else:
            print("FR...")
            browser.get("https://www.soscuisine.com/?lang=fr&sos_r="+region)
            sleep(2)
            browser.get("https://www.soscuisine.com/speciaux-circulaires/")
            sleep(5)
            browser.find_element_by_xpath("//select[@name='example_length']/option[text()='ALL']").click()
            sleep(15)

            html = browser.page_source
            file_object = open("/tmp/soscuisine/soscuisine_"+ id_database + "_fr.html", "w+")
            file_object.write(html)
            file_object.close()

            soup = BeautifulSoup(browser.page_source, 'lxml')
            table = soup.find_all('table')[0]

            table_rows = table.find_all('tr')
            for tr in table_rows:
                td = tr.find_all('td')
                nb_colonne = 0
                for i in td:
                    # id_produit
                    if nb_colonne == 1:
                        row = [i]
                        rows = str(row).split("data-soscuisine-sip=\"")
                        rows = str(rows[1]).split("\"")
                        row = rows[0]
                        id_produit = row
                    # description du produit
                    elif nb_colonne == 3:
                        row = [i]
                        rows = str(row).split("<td>")
                        if len(rows)>1:
                            rows = str(rows[1]).split("</td>")
                            description = rows[0]

                    # id_produit, id_enseigne, id_database
                    elif nb_colonne == 5:
                        row = [i]
                        rows = str(row).split("\"")
                        if rows[1] in enseignes:
                            row = enseignes[rows[1]]
                            id_enseigne = row
                            id_produit_id_enseigne_id_database = str(id_produit) + "_" + str(id_enseigne) + "_" + str(id_database)
                            if id_produit_id_enseigne_id_database in data_soscuisine:
                                data_soscuisine[id_produit_id_enseigne_id_database][6] = description
                    nb_colonne = nb_colonne + 1
            print(len(data_soscuisine))
# print(data_soscuisine)
print(len(data_soscuisine))
browser.close()

#sauvegarde dataset in /tmp/soscuisine/soscusine.csv
file_csv = open("/tmp/soscuisine/soscuisine.csv", "w+")
for key, value in data_soscuisine.items():
    file_csv.write('<>'.join([str(x) for x in value]))
    file_csv.write("\n")

file_csv.close()
