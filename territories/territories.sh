# Author:        Dominik Pop
# Date:          4.10.2022
# VUT FIT, 3BIT

# State:
echo "0, 1, Česká republika"
echo "Stát"

# Regiony:
cat downloaded_files/RS.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 1, $1, $2, 0, $3)}' | tail -n +2 | head -n -1
cat downloaded_files/RS.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 1, $1, $2, 0, $3)}' | tail -n 1
echo "Region"

# Kraje: 
cat downloaded_files/VUSC.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 2, $1, $2, 1, $3)}' | tail -n +2 | head -n -1
cat downloaded_files/VUSC.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 2, $1, $2, 1, $3)}' | tail -n 1
echo "Kraj"

# Okresy:
cat downloaded_files/Okres.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 3, $1, $2, 2, $3)}' | tail -n +2 | head -n -1
cat downloaded_files/Okres.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 3, $1, $2, 2, $3)}' | tail -n 1
echo "Okres"

# Obec:
cat downloaded_files/Obec.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 4, $1, $2, 3, $5)}' | tail -n +2 | head -n -1
cat downloaded_files/Obec.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 4, $1, $2, 3, $5)}' | tail -n 1
echo "Obec"

# Casti obce:
cat downloaded_files/CO.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 5, $1, $2, 4, $3)}' | tail -n +2 | head -n -1
cat downloaded_files/CO.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 5, $1, $2, 4, $3)}' | tail -n 1
echo "Casti"

# Mestske celky:
cat downloaded_files/MOMC.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 5, $1, $2, 4, $3)}' | tail -n +2 | head -n -1
cat downloaded_files/MOMC.txt | awk -F ";" '{printf("%d, %d, %s, %d, %d\n", 5, $1, $2, 4, $3)}' | tail -n 1
echo "Celky"

# # State:
# cat downloaded_files/State.txt | awk -F ";" '{printf("insert into Territory (type, RUIAN_id, name) values (%d, %d, \"%s\"); \n", 0, $1, $2)}' | tail -n +2

# # Regiony:
# echo "insert into Territory (type, RUIAN_id, name, partOfType, partOfRUIAN) values"
# cat downloaded_files/RS.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d), \n", 1, $1, $2, 0, $3)}' | tail -n +2 | head -n -1
# cat downloaded_files/RS.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d); \n", 1, $1, $2, 0, $3)}' | tail -n 1

# # Kraje: 
# echo "insert into Territory (type, RUIAN_id, name, partOfType, partOfRUIAN) values"
# cat downloaded_files/VUSC.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d), \n", 2, $1, $2, 1, $3)}' | tail -n +2 | head -n -1
# cat downloaded_files/VUSC.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d); \n", 2, $1, $2, 1, $3)}' | tail -n 1

# # Okresy:
# echo "insert into Territory (type, RUIAN_id, name, partOfType, partOfRUIAN) values"
# cat downloaded_files/Okres.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d), \n", 3, $1, $2, 2, $3)}' | tail -n +2 | head -n -1
# cat downloaded_files/Okres.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d); \n", 3, $1, $2, 2, $3)}' | tail -n 1

# # Obec:
# echo "insert into Territory (type, RUIAN_id, name, partOfType, partOfRUIAN) values"
# cat downloaded_files/Obec.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d), \n", 4, $1, $2, 3, $5)}' | tail -n +2 | head -n -1
# cat downloaded_files/Obec.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d); \n", 4, $1, $2, 3, $5)}' | tail -n 1

# # Casti obce:
# echo "insert into Territory (type, RUIAN_id, name, partOfType, partOfRUIAN) values"
# cat downloaded_files/CO.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d), \n", 5, $1, $2, 4, $3)}' | tail -n +2 | head -n -1
# cat downloaded_files/CO.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d); \n", 5, $1, $2, 4, $3)}' | tail -n 1

# # Mestske celky:
# echo "insert into Territory (type, RUIAN_id, name, partOfType, partOfRUIAN) values"
# cat downloaded_files/MOMC.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d), \n", 5, $1, $2, 4, $3)}' | tail -n +2 | head -n -1
# cat downloaded_files/MOMC.txt | awk -F ";" '{printf("(%d, %d, \"%s\", %d, %d); \n", 5, $1, $2, 4, $3)}' | tail -n 1