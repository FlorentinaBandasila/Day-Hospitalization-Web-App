const mockSpitalizariData = [
    {
        id: 1,
        data: '08.02.2026',
        nume: 'Popescu Ion',
        cnp: '1234567890123',
        sectie: 'Cardiologie',
        status: 'completed',
        ultimaModificare: '09.02.2026 - Dr. Ionescu',
        details: {
            // Date administrative
            judet: 'București',
            localitate: 'București',
            spital: 'Spitalul Clinic Județean',
            sectie: 'Cardiologie',
            
            // Date identificare
            nume: 'Popescu',
            prenume: 'Ion',
            cnp: '1234567890123',
            sex: 'M',
            dataNasterii: '1965-05-15',
            
            // Date personale
            grupSanguin: 'A',
            rh: 'pozitiv',
            alergicLa: 'Penicilină',
            
            // Domiciliu
            domiciliuJudet: 'București',
            domiciliuLocalitate: 'București',
            domiciliuMediu: 'urban',
            domiciliuStrada: 'Calea Victoriei',
            domiciliuNumar: '42',
            
            // Resedinta
            resedintaSameDomiciliu: true,
            
            // Date sociale
            cetatenie: 'romana',
            ocupatia: 'pensionar',
            locDeMunca: 'Retras din activitate',
            nivelInstruire: 'superior',
            
            // Asigurat
            statutAsigurat: 'cnas',
            categorieAsigurat: 'pensionar',
            
            // Diagnostic
            nrRegistru: 'REG-2026-001',
            tipServicii: 'tratamente',
            diagnosticPrincipal: 'Hipertensiune arterială de grad III',
            codICD: 'I10',
            diagnosticeSecundare: ['Diabet zaharat tip 2', 'InsuficiențăCard'],
            
            // Investigatii
            explorariFunctionale: [
                { denumire: 'Electrocardiogramă', cod: 'ECG', numar: '1' },
                { denumire: 'Test efort cardiac', cod: 'EFORT', numar: '1' }
            ],
            investigatiiRadiologice: [
                { denumire: 'Radiografie torace', cod: 'RX-T', numar: '1' }
            ],
            alteProceduri: [
                { denumire: 'Masaj cardiac', cod: 'PROC-001', numar: '3' }
            ],
            analizeLaborator: [
                { denumire: 'Analiză sânge complet', cod: 'LAB-001', numar: '1' },
                { denumire: 'Profil lipidic', cod: 'LAB-002', numar: '1' }
            ],
            
            // Tratamente
            tratamente: [
                { data: '08.02.2026', descriere: 'Enalapril 10mg oral x2/zi' },
                { data: '08.02.2026', descriere: 'Metoprolol 50mg oral x1/zi' },
                { data: '09.02.2026', descriere: 'Continuare medicație' }
            ],
            epicriza: 'Pacient cu hipertensiune arterială compensată. Medicație bine tolerată. Control TA normalizat. Recomandări: continuare medicație, dietă hipocalorică, activitate fizică moderată.'
        }
    },
    {
        id: 3,
        data: '15.01.2026',
        nume: 'Popescu Ion',
        cnp: '1234567890123',
        sectie: 'Cardiologie',
        status: 'completed',
        ultimaModificare: '18.01.2026 - Dr. Ionescu',
        details: {
            judet: 'București',
            localitate: 'București',
            spital: 'Spitalul Clinic Județean',
            sectie: 'Cardiologie',
            nume: 'Popescu',
            prenume: 'Ion',
            cnp: '1234567890123',
            sex: 'M',
            dataNasterii: '1965-05-15',
            grupSanguin: 'A',
            rh: 'pozitiv',
            alergicLa: 'Penicilină',
            domiciliuJudet: 'București',
            domiciliuLocalitate: 'București',
            domiciliuMediu: 'urban',
            domiciliuStrada: 'Calea Victoriei',
            domiciliuNumar: '42',
            resedintaSameDomiciliu: true,
            cetatenie: 'romana',
            ocupatia: 'pensionar',
            locDeMunca: 'Retras din activitate',
            nivelInstruire: 'superior',
            statutAsigurat: 'cnas',
            categorieAsigurat: 'pensionar',
            nrRegistru: 'REG-2026-002',
            tipServicii: 'tratamente',
            diagnosticPrincipal: 'Angina pectorală stabilă',
            codICD: 'I20',
            diagnosticeSecundare: ['Hipertensiune arterială', 'Dislipidemie'],
            explorariFunctionale: [
                { denumire: 'Electrocardiogramă', cod: 'ECG', numar: '2' },
                { denumire: 'Ecocardiografie', cod: 'ECHO', numar: '1' }
            ],
            investigatiiRadiologice: [
                { denumire: 'Radiografie torace', cod: 'RX-T', numar: '1' },
                { denumire: 'Angiografie coronară', cod: 'ANGIO', numar: '1' }
            ],
            alteProceduri: [
                { denumire: 'Injecție sublingual nitrat', cod: 'PROC-002', numar: '2' }
            ],
            analizeLaborator: [
                { denumire: 'Troponine T sensitivă', cod: 'LAB-003', numar: '1' },
                { denumire: 'BNP', cod: 'LAB-004', numar: '1' }
            ],
            tratamente: [
                { data: '15.01.2026', descriere: 'Aspirină 100mg oral/zi' },
                { data: '15.01.2026', descriere: 'Atenolol 50mg oral x1/zi' },
                { data: '16.01.2026', descriere: 'Atorvastatină 40mg oral/zi' }
            ],
            epicriza: 'Pacient cu angina pectorală stabilă. Inspecție coronară efectuată cu succes. Leziuni moderate pe RIA și RCD. Stent metalizat plasat. Postprocedural: bine. Tratament dublu antiagregant instituit.'
        }
    },
    {
        id: 4,
        data: '05.02.2026',
        nume: 'Ionescu Maria',
        cnp: '2987654321098',
        sectie: 'Oncologie',
        status: 'completed',
        ultimaModificare: '10.02.2026 - Dr. Popescu',
        details: {
            judet: 'Cluj',
            localitate: 'Cluj-Napoca',
            spital: 'Spitalul Municipal',
            sectie: 'Oncologie',
            nume: 'Ionescu',
            prenume: 'Maria',
            cnp: '2987654321098',
            sex: 'F',
            dataNasterii: '1978-09-22',
            grupSanguin: 'O',
            rh: 'pozitiv',
            alergicLa: 'Metottrexat',
            domiciliuJudet: 'Cluj',
            domiciliuLocalitate: 'Cluj-Napoca',
            domiciliuMediu: 'urban',
            domiciliuStrada: 'Str. Gheorghe Doja',
            domiciliuNumar: '15',
            resedintaSameDomiciliu: true,
            cetatenie: 'romana',
            ocupatia: 'angajat',
            locDeMunca: 'SC Química SRL',
            nivelInstruire: 'profesional',
            statutAsigurat: 'cnas',
            categorieAsigurat: 'salariat',
            nrRegistru: 'REG-2026-003',
            tipServicii: 'chimiotherapie',
            diagnosticPrincipal: 'Cancer pulmonar stadium IIIB',
            codICD: 'C78.0',
            diagnosticeSecundare: ['Efuzie pleurală', 'Anemia'],
            explorariFunctionale: [
                { denumire: 'Spirometrie', cod: 'SPIRO', numar: '1' },
                { denumire: 'Test functie pulmonară', cod: 'FEV1', numar: '1' }
            ],
            investigatiiRadiologice: [
                { denumire: 'CT torace', cod: 'CT-T', numar: '1' },
                { denumire: 'PET-CT', cod: 'PET', numar: '1' }
            ],
            alteProceduri: [
                { denumire: 'Puncție pleurală', cod: 'PROC-003', numar: '1' }
            ],
            analizeLaborator: [
                { denumire: 'Hemoglobină', cod: 'LAB-005', numar: '1' },
                { denumire: 'Marcatori tumorali CEA', cod: 'LAB-006', numar: '1' }
            ],
            tratamente: [
                { data: '05.02.2026', descriere: 'Ciclu chimiotherapie: Cisplatină + Gemcitabina' },
                { data: '05.02.2026', descriere: 'Antiemetic: Ondansetron IV' },
                { data: '06.02.2026', descriere: 'Suport nutritiv cu suplementare proteică' }
            ],
            epicriza: 'Pacient cu cancer pulmonar neoplazic. Program de chimiotherapie neoadiuvantă inițiat. Ciclu 1 tolerabil. Efecte adverse minore (nausee controlate). Reevaluare imagistică după 3 cicluri planificată.'
        }
    },
    {
        id: 5,
        data: '20.01.2026',
        nume: 'Kovacs Gheorghe',
        cnp: '3456789012345',
        sectie: 'Neurologie',
        status: 'completed',
        ultimaModificare: '22.01.2026 - Dr. Mihai',
        details: {
            judet: 'Timișoara',
            localitate: 'Timișoara',
            spital: 'Spitalul de Urgență',
            sectie: 'Neurologie',
            nume: 'Kovacs',
            prenume: 'Gheorghe',
            cnp: '3456789012345',
            sex: 'M',
            dataNasterii: '1952-03-10',
            grupSanguin: 'B',
            rh: 'negativ',
            alergicLa: 'Fenitoină',
            domiciliuJudet: 'Timișoara',
            domiciliuLocalitate: 'Timișoara',
            domiciliuMediu: 'urban',
            domiciliuStrada: 'Str. Mitropolitul Andrei Șaguna',
            domiciliuNumar: '8',
            resedintaSameDomiciliu: true,
            cetatenie: 'romana',
            ocupatia: 'pensionar',
            locDeMunca: 'Retras din activitate',
            nivelInstruire: 'liceal',
            statutAsigurat: 'cnas',
            categorieAsigurat: 'pensionar',
            nrRegistru: 'REG-2026-004',
            tipServicii: 'investigatii',
            diagnosticPrincipal: 'Parkinson idiopatic stadium II',
            codICD: 'G20',
            diagnosticeSecundare: ['Tremor esențial', 'Tulburări cognitive minore'],
            explorariFunctionale: [
                { denumire: 'EEG', cod: 'EEG', numar: '1' },
                { denumire: 'EMG memb inferioare', cod: 'EMG', numar: '1' }
            ],
            investigatiiRadiologice: [
                { denumire: 'RMN creier', cod: 'RMN-C', numar: '1' },
                { denumire: 'Scintigrafie iode MIBG', cod: 'SCINTI', numar: '1' }
            ],
            alteProceduri: [],
            analizeLaborator: [
                { denumire: 'Profil biochimie complet', cod: 'LAB-007', numar: '1' },
                { denumire: 'Vitamina B12', cod: 'LAB-008', numar: '1' }
            ],
            tratamente: [
                { data: '20.01.2026', descriere: 'Levodopa + carbidopa 250/25mg x3/zi' },
                { data: '20.01.2026', descriere: 'Domperidone 10mg x3/zi pre-meal' },
                { data: '21.01.2026', descriere: 'Terapie fizică și reabilitare' }
            ],
            epicriza: 'Pacient cu Parkinson stadium II, cu tremor moderat și rigiditate. Investigații imagistice: atrofie cerebelară minora, fără leziuni focale. RMN normal. Medicație inițiată cu bună tolerabiitate. Recomandări: reabilitare neuromotorie continuă, suport psiho-social.'
        }
    }
];
