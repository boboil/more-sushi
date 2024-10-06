<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Title</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body style="margin-left: auto; margin-right: auto;">
<page size="A4" layout="landscape">
    <header class="clearfix" style="position: relative;">
        <img src="{{asset('images/pdf/header-bg.png')}}" alt="" style="position: absolute; width: 100%; height: 100%; object-fit: cover">
        <div class="container">
            <div id="logo" style="margin-top: 0;">
                <img src="{{asset('images/pdf/logo.png')}}" style="display: block; width: 70px; height: auto;">
                <div class="employee-name" style="margin-top: 16px; font-size: 24px; line-height: 1; color: #FFFFFF;">
                    Maksim Aleshchenko
                </div>
            </div>
            <div id="position">
                <div class="employee-position">Senior Software Engineer</div>
                <div class="employee-characteristics-group">
                    <div class="employee-characteristics">
                        Timezone: <span>GMT+03:00 (EET)</span>
                        <img src="{{asset('images/pdf/separator.png')}}" alt="separator">
                    </div>
                    <div class="employee-characteristics">
                        Language: <span>UA, RU, EN</span>
                        <img src="{{asset('images/pdf/separator.png')}}" alt="separator">
                    </div>
                    <div class="employee-characteristics">
                        Experience: 10+ years
                        <img src="{{asset('images/pdf/separator.png')}}" alt="separator">
                    </div>
                    <div class="employee-characteristics">
                        Education: <span>Software engineer</span>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <main style="padding-bottom: 50px; padding-top: 24px">
        <div class="container" style="padding: 0">
            <div class="brief-info">
                <h3>Brief info</h3>
                <div>
                    <p>
                        Maksim has experience working with clients from a variety of industries, including finance,
                        healthcare, education, and e-commerce.His development process is agile and collaborative,
                        ensuring that clients are involved at every step of the way and that their feedback is
                        incorporated into the final product.
                    </p>
                </div>
            </div>
            <div class="tech-skill-block">
                <h3 style="margin: 0">Tech skills</h3>
                <div class="tech-skill-list">
                    <ul>
                        <li style="background: #D7DDE2; border-radius: 2px;">php</li>
                        <li style="background: #D7DDE2; border-radius: 2px;">Python</li>
                        <li style="background: #D7DDE2; border-radius: 2px;">PHP framework (Laravel, Symfony2, Yii2, CakePHP)</li>
                        <li style="background: #D7DDE2; border-radius: 2px;">DB: MySQL, MongoDB, MSSQL, Postgresql</li>
                        <li style="background: #D7DDE2; border-radius: 2px;">DB: MySQL, MongoDB, MSSQL, Postgresql</li>
                        <li style="background: #D7DDE2; border-radius: 2px;">Python</li>
                        <li style="background: #D7DDE2; border-radius: 2px;">DB: MySQL, MongoDB, MSSQL, Postgresql</li>
                        <li style="background: #D7DDE2; border-radius: 2px;">DB: MySQL, MongoDB, MSSQL, Postgresql</li>
                        <li style="background: #D7DDE2; border-radius: 2px;">Python</li>
                        <li style="background: #D7DDE2; border-radius: 2px;">Python</li>
                        <li style="background: #D7DDE2; border-radius: 2px;">DB: MySQL, MongoDB, MSSQL, Postgresql</li>
                    </ul>
                </div>
            </div>
            <div class="projects-block">
                <h2 style="margin-bottom: 14px;">Projects</h2>
                <div class="projects-item">
                    <h3 style="margin-bottom: 8px">CatalogueRouge</h3>
                    <p>
                        Development of a digital art library that offers instant access to digital versions
                        of artistic publications.
                    </p>
                    <div class="role-block">
                        <span>Role</span>
                        <p>Backend Developer. API Development, Product Subscription Integration.</p>
                    </div>
                    <div class="stack-block">
                        <span>Stack</span>
                        <div class="tech-skill-list">
                            <ul>
                                <li>php</li>
                                <li>Python</li>
                                <li>PHP framework (Laravel, Symfony2, Yii2, CakePHP)</li>
                                <li>DB: MySQL, MongoDB, MSSQL, Postgresql</li>
                            </ul>
                        </div>
                    </div>
                    <div class="role-block">
                        <span><img src="{{asset('images/pdf/link-image.png')}}" alt="link"></span>
                        <p>https://artd.ai/</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <div class="container" style="padding-bottom: 27px; padding-top: 0">
            © CPCS 2023
        </div>
    </footer>
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Manrope', sans-serif;
        }

        @page {
            size: 210mm 297mm;
            margin: 10mm 10mm 0;
        }

        page {
            background: white;
            display: block;
            margin: 0 auto;
            position: relative;
        }

        page[size="A4"][layout="portrait"] {
            width: 190mm;
            height: 277mm;
        }

        page[size="A4"][layout="landscape"] {
            width: 277mm;
            height: 190mm;
        }

        @media print {
            body, page {
                margin: 0;
                box-shadow: none;
            }
        }

        .container {
            margin: 0 0 0 50px;
            padding-top: 48px;
            padding-bottom: 16px;
            position: relative;
        }
        body {
            position: relative;
            max-width: 100%;
            width: 100%;
            height: 29.7cm;
            margin: 0 auto;
            font-family: Manrope, sans-serif;
            font-size: 12px;
        }

        .employee-name {
            color: #FFF;
            font-size: 24px;
            font-style: normal;
            font-weight: 400;
            line-height: 160%;
        }

        #position {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
            align-self: stretch;
        }

        .employee-characteristics-group {
            display: flex;
            align-items: flex-start;
            align-content: flex-start;
            gap: 4px;
            align-self: stretch;
            flex-wrap: wrap;
        }

        .employee-position {
            display: flex;
            align-items: flex-start;
            gap: 4px;
            color: #FDB714;
            font-family: Manrope, serif;
            font-size: 18px;
            font-style: normal;
            font-weight: 700;
            line-height: 160%;
        }

        .employee-characteristics {
            color: #FFF;
            font-size: 10px;
            font-style: normal;
            font-weight: 700;
            line-height: 160%;
            display: flex;
            align-items: flex-start;
            gap: 4px;
        }

        .employee-characteristics span {
            font-weight: 500;
        }

        .tech-skill-block {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 14px;
        }

        .tech-skill-list ul {
            list-style: none;
            display: flex;
            align-items: flex-start;
            align-content: flex-start;
            gap: 4px;
            align-self: stretch;
            flex-wrap: wrap;
            padding: 0;
        }

        .tech-skill-list ul li {
            display: flex;
            padding: 4px;
            justify-content: center;
            align-items: center;
            gap: 8px;
            border-radius: 2px;
            background: #D7DDE2;
        }


        .projects-block h2 {
            color: #040C23;
            font-family: Manrope, sans-serif;
            font-size: 18px;
            font-style: normal;
            font-weight: 700;
            line-height: 160%;
        }

        .projects-block {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 14px;
            flex: 1 0 0;
        }

        .stack-block, .role-block {
            display: flex;
            align-items: center;
            gap: 12px;
            align-self: stretch;
            flex-direction: row;
            flex-wrap: nowrap;
        }

        .stack-block span, .role-block span {
            width: 72px;
            height: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #040C23;
            font-size: 10px;
            font-style: normal;
            font-weight: 700;
            line-height: 160%;
        }

        .stack-block .tech-skill-list {
            display: flex;
            align-items: flex-start;
            align-content: flex-start;
            gap: 4px;
            flex: 1 0 0;
            align-self: stretch;
            flex-wrap: wrap;
            color: #11131B;
            font-size: 10px;
            font-style: normal;
            font-weight: 500;
            line-height: 160%;
        }
        .projects-item {
            padding-bottom: 24px;
        }
        footer {
            display: flex;
            flex-direction: column;
            flex: 1 0 0;
            color: #040C23;
            font-size: 10px;
            font-style: normal;
            font-weight: 400;
            line-height: 160%;
        }
    </style>
</page>
</body>

</html>
