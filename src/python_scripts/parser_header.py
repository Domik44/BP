from orator import DatabaseManager, Model
from orator.orm import has_many, has_one, belongs_to_many

config = {
    'default': 'mysql',
    'mysql': {
        'driver': 'mysql',
        'host': 'localhost',
        'database': 'xpopdo00',
        'user': 'xpopdo00',
        'password': 'password',
    }
}

db = DatabaseManager(config)
Model.set_connection_resolver(db)


class AppUser(Model):
    __table__ = 'User'
    __timestamps__ = False


class GFile(Model):
    __table__ = 'GFile'
    __timestamps__ = False
    __fillable__ = ['name', 'userID']


class Person(Model):
    __table__ = 'Person'
    __primary_key__ = 'personID'
    __timestamps__ = False
    __fillable__ = ['gedcomID', 'personINDI', 'firstName',
                    'lastName', 'gender',
                    'birthDate', 'birthYear', 'birthPlaceStr', 'birthPlaceID', 'deathDate', 'deathYear', 'deathPlaceStr',
                    'deathPlaceID', 'fatherID', 'motherID']

    def get_parents(self):
        father = None
        mother = None
        if self.fatherID is not None:
            father = Person.find(self.fatherID)
        if self.motherID is not None:
            mother = Person.find(self.motherID)

        return father, mother

    def get_siblings(self, father, mother):
        siblings = []
        if father is None and mother is None:
            return siblings
        elif father is not None and mother is not None:
            siblingsQuery = Person.where('fatherID', '=', father.personID).or_where('motherID', '=', mother.personID)
        elif father is not None and mother is None:
            siblingsQuery = Person.where('fatherID', '=', father.personID)
        else:  # father is None and mother is not None:
            siblingsQuery = Person.where('motherID', '=', mother.personID)

        siblings = [i for i in siblingsQuery.get() if i.personID != self.personID]

        return siblings


    def get_children(self):
        children = [i for i in Person.where('motherID', '=', self.personID).or_where('fatherID', '=', self.personID).get()]

        return children

    def get_parent_family(self):
        if self.motherID is None and self.fatherID is None:
            return None
        family = Family.where('husbandID', '=', self.fatherID).where('wifeID', '=', self.motherID).first()

        return family

    def get_marriage_families(self):
        families = [i for i in Family.where('husbandID', '=', self.personID).or_where('wifeID', '=', self.personID).get()]
        return families


    def get_spouses(self):
        families = self.get_spouse_families()
        spousesIDs = []
        for family in families:
            if family.husbandID != self.personID:
                spousesIDs.append(family.husbandID)
            else:
                spousesIDs.append(family.wifeID)

        spouses = [i for i in Person.where_in('personID', spousesIDs).get()]

        return spouses


class Family(Model):
    __table__ = 'Family'
    __primary_key__ = 'familyID'
    __timestamps__ = False
    __fillable__ = ['gedcomID', 'familyINDI', 'firstName',
                    'lastName', 'gender',
                    'marriageDate', 'marriageYear', 'marriagePlaceStr', 'marriagePlaceID', 'marriagePlaceID2',
                    'husbandID', 'wifeID']

    def get_children_of_family(self):
        children = [i for i in Person.where('motherID', '=', self.wifeID).where('fatherID', '=', self.husbandID).get()]

        return children

    @staticmethod
    def get_family_by_parents(husband, wife):
        wifeID = wife.personID if wife is not None else None
        husbandID = husband.personID if husband is not None else None
        family = Family.where('husbandID', '=', husbandID).where('wifeID', '=', wifeID).first()

        return family

class ParishBook(Model):
    __table__ = 'ParishBook'
    __fillable__ = ['fromYear', 'toYear', 'url',
                    'originator', 'originatorType',
                    'birthFromYear', 'birthToYear', 'deathFromYear',
                    'deathToYear', 'marriageFromYear', 'marriageToYear',
                    'birthIndexFromYear', 'birthIndexToYear', 'deathIndexFromYear',
                    'deathIndexToYear', 'marriageIndexFromYear', 'marriageIndexToYear']
    __timestamps__ = False

class Territory(Model):
    __table__ = 'Territory'
    __timestamps__ = False
    __fillable__ = ['type', 'RUIAN_id', 'name',
                    'partOf', 'longitude',
                    'latitude']

    @belongs_to_many('Territory_ParishBook', 'territoryId', 'bookId')
    def books(self):
        return ParishBook

class Record(Model):
    __table__ = 'Record'
    __timestamps__ = False
    __fillable__ = ['type', 'missing', 'gedcomID',
                    'personID', 'familyID',
                    'note']
