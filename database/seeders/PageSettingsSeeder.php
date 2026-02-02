<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $page_settings_record = [
            [
                'id' => '1',
                'name' => 'Contact us',
                'ja_name' => 'お問い合わせ',
                'pt_name' => 'Contate-nos',
                'vi_name' => 'Liên hệ với chúng tôi',
                'he_name' => 'צור איתנו קשר',
                'de_name' => 'Kontaktieren Sie uns',
                'es_name' => 'Contacta con nosotras',
                'fr_name' => 'Contactez-nous',
                'ko_name' => '문의하기',
                'zh_name' => '联系我们',
                'fil_name' => 'Makipag-ugnayan sa amin',
                'ar_name' => 'اتصل بنا' ,
                'type' => 1,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Media and Business Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">General and Technical Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">&nbsp;&nbsp;</span></p>',
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<div id="tw-target-rmn-container" class="tw-target-rmn tw-ta-container F0azHf tw-nfl" style="overflow: hidden; position: relative; outline: 0px; color: #bdc1c6; font-family: arial, sans-serif; font-size: 0px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; white-space: normal; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; background-color: #303134; text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;"></div>
                <div id="tw-target-text-container" class="tw-ta-container F0azHf tw-lfl" style="overflow: hidden; position: relative; outline: 0px; color: #bdc1c6; font-family: arial, sans-serif; font-size: 0px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; white-space: normal; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; background-color: #303134; text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;" tabindex="0">
                <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Media and Business Inquiries</span></strong></p>
                <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">General and Technical Inquiries</span></strong></p>
                <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">&nbsp;&nbsp;</span></p>
                <pre id="tw-target-text" class="tw-data-text tw-text-large tw-ta" dir="rtl" style="unicode-bidi: isolate; font-size: 28px; line-height: 32px; background-color: transparent; border: none; padding: 2px 0.14em 2px 0px; position: relative; margin: -2px 0px; resize: none; font-family: inherit; overflow: hidden; text-align: right; width: 270px; white-space: pre-wrap; overflow-wrap: break-word; color: #e8eaed;" data-placeholder="Translation"></pre>
                </div>',
            ],
            [
                'id' => 2,
                'name' => 'FAQ',
                'ja_name' => 'よくある質問',
                'pt_name' => 'Perguntas frequentes',
                'vi_name' => 'Câu hỏi thường gặp',
                'he_name' => 'שאלות נפוצות',
                'de_name' => 'Häufig gestellte Fragen',
                'es_name' => 'Preguntas frecuentes',
                'fr_name' => 'FAQ',
                'ko_name' => '자주 묻는 질문',
                'zh_name' => '常问问题',
                'fil_name' => 'FAQ',
                'ar_name' => 'التعليمات' ,
                'type' => 1,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Why us?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We&nbsp;<strong>CONNECT BUSINESSES</strong>&nbsp;with their customer base and help acquire new ones.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We build&nbsp;<strong>INNOVATIVE AND FULLY INTEGRATED SOLUTIONS</strong>&nbsp;to help increase our clients&rsquo; brand and enhance user experience.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are&nbsp;<strong>RESULT ORIENTED</strong>&nbsp;and will provide a framework to attain digital transformation on your business methodologies.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are your&nbsp;<strong>TECHNOLOGY APP PLATFORM PARTNER</strong>. Your way, anytime, anywhere.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is it available now?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Yes, join us and download apps! Sign up and share us with your friends and family.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Why us?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We&nbsp;<strong>CONNECT BUSINESSES</strong>&nbsp;with their customer base and help acquire new ones.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We build&nbsp;<strong>INNOVATIVE AND FULLY INTEGRATED SOLUTIONS</strong>&nbsp;to help increase our clients&rsquo; brand and enhance user experience.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are&nbsp;<strong>RESULT ORIENTED</strong>&nbsp;and will provide a framework to attain digital transformation on your business methodologies.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are your&nbsp;<strong>TECHNOLOGY APP PLATFORM PARTNER</strong>. Your way, anytime, anywhere.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is it available now?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Yes, join us and download apps! Sign up and share us with your friends and family.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>',
            ],
            [
                'id' => 3,
                'name' => 'Disclaimer',
                'ja_name' => '免責事項',
                'pt_name' => 'Isenção de responsabilidade',
                'vi_name' => 'Tuyên bố miễn trừ trách nhiệm',
                'he_name' => 'כתב ויתור',
                'de_name' => 'Haftungsausschluss',
                'es_name' => 'Descargo de responsabilidad',
                'fr_name' => 'Clause de non-responsabilité',
                'ko_name' => '부인 성명',
                'zh_name' => '免责声明',
                'fil_name' => 'Disclaimer',
                'ar_name' => 'تنصل' ,
                'type' => 1,
                'description' => '<p>&nbsp;Disclaimer&nbsp;</p>
                    <p>Please read this disclaimer ("disclaimer") carefully before using app (&ldquo;app&rdquo;,</p>
                    <p>"service") operated by us.</p>
                    <p>The content displayed on the app is the intellectual property of the app. You may not</p>
                    <p>reuse, republish, or reprint such content without our written consent.</p>
                    <p>All information posted is merely for educational and informational purposes. It is not intended</p>
                    <p>as a substitute for professional advice. Should you decide to act upon any information on this</p>
                    <p>app, you do so at your own risk.</p>
                    <p>While the information on this app has been verified to the best of our abilities, we cannot</p>
                    <p>guarantee that there are no mistakes or errors.</p>
                    <p>We reserve the right to change this policy at any given time, of which you will be promptly</p>
                    <p>updated. If you want to make sure that you are up to date with the latest changes, we advise</p>
                    <p>you to frequently visit this page.</p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p>&nbsp;Disclaimer&nbsp;</p>
                    <p>Please read this disclaimer ("disclaimer") carefully before using app (&ldquo;app&rdquo;,</p>
                    <p>"service") operated by us.</p>
                    <p>The content displayed on the app is the intellectual property of the app. You may not</p>
                    <p>reuse, republish, or reprint such content without our written consent.</p>
                    <p>All information posted is merely for educational and informational purposes. It is not intended</p>
                    <p>as a substitute for professional advice. Should you decide to act upon any information on this</p>
                    <p>app, you do so at your own risk.</p>
                    <p>While the information on this app has been verified to the best of our abilities, we cannot</p>
                    <p>guarantee that there are no mistakes or errors.</p>
                    <p>We reserve the right to change this policy at any given time, of which you will be promptly</p>
                    <p>updated. If you want to make sure that you are up to date with the latest changes, we advise</p>
                    <p>you to frequently visit this page.</p>',
            ],
            [
                'id' => 4,
                'name' => 'Privacy Policy',
                'ja_name' => 'プライバシーポリシー',
                'pt_name' => 'política de Privacidade',
                'vi_name' => 'Chính sách bảo mật',
                'he_name' => 'מדיניות פרטיות',
                'de_name' => 'Datenschutzrichtlinie',
                'es_name' => 'política de privacidad',
                'fr_name' => 'politique de confidentialité',
                'ko_name' => '개인정보 보호정책',
                'zh_name' => '隐私政策',
                'fil_name' => 'Patakaran sa Privacy',
                'ar_name' => 'Privacy Policy' ,
                'type' => 1,
                'description' => '<div style="margin-left: 20px; color: black; background-color: ghostwhite; padding-top: 20px;">
                    <p><strong style="font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Privacy Policy</span></strong></p>
                    <p>Fixerity built the Fixerity  app as open-source/free app. This Privacy Policy for that Fixerity (Fixerity app.startuptrinity) will collect the personal information like name, email, contacy number, etc when you use our mobile application.</p>
                    <p><strong>Information Collection and Use</strong></p>
                    <p>For a better experience, while using our Service, We may require you to provide us with certain personally identifiable information add whatever else you collect here(in https://fixerity.com), e.g. users name, address, location, pictures The information that We request will be retained on your device and is not collected by us in any way/[retained by us and used as described in this privacy policy.</p>
                    <p>The collected infromation is shared with third-party services because using the customer data we can provide personalize app behavour, our service &amp; product improvement.</p>
                    <p><strong>Log Data</strong></p>
                    <p>We want to inform you that whenever you use our Service, in a case of an error in the app We collect data and information (through third party products) on your phone called Log Data. This Log Data may include information such as your device Internet Protocol (&ldquo;IP&rdquo;) address, device name, operating system version, the configuration of the app when utilizing [my/our] Service, the time and date of your use of the Service, and other statistics.</p>
                    <p><strong>Cookies</strong></p>
                    <p>Cookies are files with a small amount of data that are commonly used as anonymous unique identifiers. These are sent to your browser from the websites that you visit and are stored on your device\'s internal memory.</p>
                    <p>This Service does not use these &ldquo;cookies&rdquo; explicitly. However, the app may use third party code and libraries that use &ldquo;cookies&rdquo; to collect information and improve their services. You have the option to either accept or refuse these cookies and know when a cookie is being sent to your device. If you choose to refuse our cookies, you may not be able to use some portions of this Service.</p>
                    <p><strong>Service Providers</strong></p>
                    <p>We may employ third-party companies and individuals due to the following reasons:</p>
                    <ul>
                    <li>To facilitate our Service;</li>
                    <li>To provide the Service on our behalf;</li>
                    <li>To perform Service-related services; or</li>
                    <li>To assist us in analyzing how our Service is used.</li>
                    </ul>
                    <p>We want to inform users of this Service that these third parties have access to your Personal Information. The reason is to perform the tasks assigned to them on our behalf. However, they are obligated not to disclose or use the information for any other purpose.</p>
                    <p><strong>Security</strong></p>
                    <p>We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it. But remember that no method of transmission over the internet, or method of electronic storage is 100% secure and reliable, and We cannot guarantee its absolute security.</p>
                    <p><strong>Changes to This Privacy Policy</strong></p>
                    <p>We may update our Privacy Policy from time to time. Thus, you are advised to review this page periodically for any changes. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
                    <p><strong>Contact Us</strong></p>
                    <p>If you have any questions or suggestions about our] Privacy Policy, do not hesitate to contact us at Whitelabelfoxapp@gmail.com.</p>
                    </div>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p><strong style="font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Privacy Policy</span></strong></p>
                        <p>Fixerity built the Fixerity  app as open source/free/freemium app. This SERVICE is provided by Fixerity and is intended for use as is.</p>
                        <p>This page is used to inform visitors regarding our policies with the collection, use, and disclosure of Personal Information if anyone decided to use [my/our] Service.</p>
                        <p>If you choose to use our Service, then you agree to the collection and use of information in relation to this policy. The Personal Information that We collect is used for providing and improving the Service. We will not use or share your information with anyone except as described in this Privacy Policy.</p>
                        <p>The terms used in this Privacy Policy have the same meanings as in our Terms and Conditions, which is accessible at Fixerity  unless otherwise defined in this Privacy Policy.</p>
                        <p><strong>Information Collection and Use</strong></p>
                        <p>For a better experience, while using our Service, We may require you to provide us with certain personally identifiable information add whatever else you collect here, e.g. users name, address, location, pictures The information that We request will be retained on your device and is not collected by us in any way/[retained by us and used as described in this privacy policy.</p>
                        <p>The app does use third party services that may collect information used to identify you.</p>
                        <p>Link to privacy policy of third party service providers used by the app</p>
                        <p><a href="https://www.google.com/policies/privacy">Google Play Services</a></p>
                        <p><a href="https://firebase.google.com/policies/analytics">Google Analytics for Firebase</a></p>
                        <p><a href="https://firebase.google.com/support/privacy/">Firebase Crashlytics</a></p>
                        <p><strong>Log Data</strong></p>
                        <p>We want to inform you that whenever you use our Service, in a case of an error in the app We collect data and information (through third party products) on your phone called Log Data. This Log Data may include information such as your device Internet Protocol (&ldquo;IP&rdquo;) address, device name, operating system version, the configuration of the app when utilizing [my/our] Service, the time and date of your use of the Service, and other statistics.</p>
                        <p><strong>Cookies</strong></p>
                        <p>Cookies are files with a small amount of data that are commonly used as anonymous unique identifiers. These are sent to your browser from the websites that you visit and are stored on your device\'s internal memory.</p>
                        <p>This Service does not use these &ldquo;cookies&rdquo; explicitly. However, the app may use third party code and libraries that use &ldquo;cookies&rdquo; to collect information and improve their services. You have the option to either accept or refuse these cookies and know when a cookie is being sent to your device. If you choose to refuse our cookies, you may not be able to use some portions of this Service.</p>
                        <p><strong>Service Providers</strong></p>
                        <p>We may employ third-party companies and individuals due to the following reasons:</p>
                        <ul>
                        <li>To facilitate our Service;</li>
                        <li>To provide the Service on our behalf;</li>
                        <li>To perform Service-related services; or</li>
                        <li>To assist us in analyzing how our Service is used.</li>
                        </ul>
                        <p>We want to inform users of this Service that these third parties have access to your Personal Information. The reason is to perform the tasks assigned to them on our behalf. However, they are obligated not to disclose or use the information for any other purpose.</p>
                        <p><strong>Security</strong></p>
                        <p>We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it. But remember that no method of transmission over the internet, or method of electronic storage is 100% secure and reliable, and We cannot guarantee its absolute security.</p>
                        <p><strong>Links to Other Sites</strong></p>
                        <p>This Service may contain links to other sites. If you click on a third-party link, you will be directed to that site. Note that these external sites are not operated by us. Therefore, We strongly advise you to review the Privacy Policy of these websites. We have no control over and assume no responsibility for the content, privacy policies, or practices of any third-party sites or services.</p>
                        <p><strong>Children&rsquo;s Privacy</strong></p>
                        <p>These Services do not address anyone under the age of 13. We do not knowingly collect personally identifiable information from children under 13. In the case We discover that a child under 13 has provided us with personal information, We immediately delete this from our servers. If you are a parent or guardian and you are aware that your child has provided us with personal information, please contact us so that We will be able to do necessary actions.</p>
                        <p><strong>Changes to This Privacy Policy</strong></p>
                        <p>We may update our Privacy Policy from time to time. Thus, you are advised to review this page periodically for any changes. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
                        <p><strong>Contact Us</strong></p>
                        <p>If you have any questions or suggestions about our] Privacy Policy, do not hesitate to contact us at Whitelabelfoxapp@gmail.com.</p>',
            ],
            [
                'id' => 5,
                'name' => 'Terms and Conditions',
                'ja_name' => '利用規約',
                'pt_name' => 'Termos e Condições',
                'vi_name' => 'Điều khoản và điều kiện',
                'he_name' => 'תנאים והגבלות',
                'de_name' => 'Geschäftsbedingungen',
                'es_name' => 'Términos y condiciones',
                'fr_name' => 'Termes et conditions',
                'ko_name' => '이용약관',
                'zh_name' => '条款和条件',
                'fil_name' => 'Mga Tuntunin at Kundisyon',
                'ar_name' => 'الأحكام والشروط' ,
                'type' => 1,
                'description' => '<div style="padding: 0px 50px;">
                    <p><strong>What personal information do we collect?</strong></p>
                    <p>When you place an order or complete a customer survey, we may collect personal information about you which may include name, email address, telephone number, location etc when voluntarily given by you. We collect this information to carry out the services offered by our app and to provide you offers and information about other services you may be interested in.</p>
                    <p>&nbsp;</p>
                    <p><strong>Who will see my personal information?</strong></p>
                    <p>Your privacy is of the utmost importance to us and no sensitive data will be shared without your consent.</p>
                    <p>&nbsp;</p>
                    <p><strong>Is my personal information secure with Fixerity ?</strong></p>
                    <p>Fixerity  will endeavor to protect your personal information from interference, modification, disclosure, misuse, loss, and unauthorized access. You are responsible for the confidentiality of your password and we strongly recommend against sharin</p>
                    </div>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<div style="padding: 0px 50px;">
                    <p><strong>What personal information do we collect?</strong></p>
                    <p>When you place an order or complete a customer survey, we may collect personal information about you which may include name, email address, telephone number, location etc when voluntarily given by you. We collect this information to carry out the services offered by our app and to provide you offers and information about other services you may be interested in.</p>
                    <p>&nbsp;</p>
                    <p><strong>Who will see my personal information?</strong></p>
                    <p>Your privacy is of the utmost importance to us and no sensitive data will be shared without your consent.</p>
                    <p>&nbsp;</p>
                    <p><strong>Is my personal information secure with Fixerity ?</strong></p>
                    <p>Fixerity  will endeavor to protect your personal information from interference, modification, disclosure, misuse, loss, and unauthorized access. You are responsible for the confidentiality of your password and we strongly recommend against sharin</p>
                    </div>',
            ],

            [
                'id' => 6,
                'name' => 'Contact us',
                'ja_name' => 'お問い合わせ',
                'pt_name' => 'Contate-nos',
                'vi_name' => 'Liên hệ với chúng tôi',
                'he_name' => 'צור איתנו קשר',
                'de_name' => 'Kontaktieren Sie uns',
                'es_name' => 'Contacta con nosotras',
                'fr_name' => 'Contactez-nous',
                'ko_name' => '문의하기',
                'zh_name' => '联系我们',
                'fil_name' => 'Makipag-ugnayan sa amin',
                'ar_name' => 'اتصل بنا' ,
                'type' => 2,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Media and Business Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">General and Technical Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">&nbsp;&nbsp;</span></p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<div id="tw-target-rmn-container" class="tw-target-rmn tw-ta-container F0azHf tw-nfl" style="overflow: hidden;position: relative; outline: 0px; color: #bdc1c6; font-family: arial, sans-serif; font-size: 0px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; white-space: normal; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; background-color: #303134; text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;"></div>
                    <div id="tw-target-text-container" class="tw-ta-container F0azHf tw-lfl" style="overflow: hidden; position: relative; outline: 0px; color: #bdc1c6; font-family: arial, sans-serif; font-size: 0px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; white-space: normal; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; background-color: #303134; text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;" tabindex="0">
                    <pre id="tw-target-text" class="tw-data-text tw-text-large tw-ta" style="unicode-bidi: isolate; line-height: 32px; border: none; padding: 2px 0.14em 2px 0px; position: relative; margin-top: -2px; margin-bottom: -2px; resize: none; font-family: inherit; overflow: hidden; width: 270px; white-space: pre-wrap; overflow-wrap: break-word;" data-placeholder="Translation"><span class="Y2IQFc" lang="ar">الاستفسارات العامة والفنية</span></pre>
                    <pre id="tw-target-text" class="tw-data-text tw-text-large tw-ta" dir="rtl" style="unicode-bidi: isolate; font-size: 28px; line-height: 32px; background-color: transparent; border: none; padding: 2px 0.14em 2px 0px; position: relative; margin: -2px 0px; resize: none; font-family: inherit; overflow: hidden; text-align: right; width: 270px; white-space: pre-wrap; overflow-wrap: break-word; color: #e8eaed;" data-placeholder="Translation"></pre>
                    </div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div id="tw-target-rmn-container" class="tw-target-rmn tw-ta-container F0azHf tw-nfl" style="overflow: hidden; position: relative; outline: 0px; color: #bdc1c6; font-family: arial, sans-serif; font-size: 0px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; white-space: normal; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; background-color: #303134; text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;"></div>
                    <div id="tw-target-text-container" class="tw-ta-container F0azHf tw-lfl" style="overflow: hidden; position: relative; outline: 0px; color: #bdc1c6; font-family: arial, sans-serif; font-size: 0px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; white-space: normal; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; background-color: #303134; text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;" tabindex="0">
                    <pre id="tw-target-text" class="tw-data-text tw-text-large tw-ta" dir="rtl" style="unicode-bidi: isolate; font-size: 28px; line-height: 32px; background-color: transparent; border: none; padding: 2px 0.14em 2px 0px; position: relative; margin: -2px 0px; resize: none; font-family: inherit; overflow: hidden; text-align: right; width: 270px; white-space: pre-wrap; overflow-wrap: break-word; color: #e8eaed;" data-placeholder="Translation"><span class="Y2IQFc" lang="ar">الاستفسارات العامة والفنية</span></pre>
                    </div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">&nbsp;&nbsp;</span></p>',
            ],
            [
                'id' => 7,
                'name' => 'FAQ',
                'ja_name' => 'よくある質問',
                'pt_name' => 'Perguntas frequentes',
                'vi_name' => 'Câu hỏi thường gặp',
                'he_name' => 'שאלות נפוצות',
                'de_name' => 'Häufig gestellte Fragen',
                'es_name' => 'Preguntas frecuentes',
                'fr_name' => 'FAQ',
                'ko_name' => '자주 묻는 질문',
                'zh_name' => '常问问题',
                'fil_name' => 'FAQ',
                'ar_name' => 'التعليمات' ,
                'type' => 2,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Why us?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We&nbsp;<strong>CONNECT BUSINESSES</strong>&nbsp;with their customer base and help acquire new ones.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We build&nbsp;<strong>INNOVATIVE AND FULLY INTEGRATED SOLUTIONS</strong>&nbsp;to help increase our clients&rsquo; brand and enhance user experience.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are&nbsp;<strong>RESULT ORIENTED</strong>&nbsp;and will provide a framework to attain digital transformation on your business methodologies.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are your&nbsp;<strong>TECHNOLOGY APP PLATFORM PARTNER</strong>. Your way, anytime, anywhere.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is it available now?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Yes, join us and download apps! Sign up and share us with your friends and family.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><br /><br /></p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Why us?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We&nbsp;<strong>CONNECT BUSINESSES</strong>&nbsp;with their customer base and help acquire new ones.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We build&nbsp;<strong>INNOVATIVE AND FULLY INTEGRATED SOLUTIONS</strong>&nbsp;to help increase our clients&rsquo; brand and enhance user experience.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are&nbsp;<strong>RESULT ORIENTED</strong>&nbsp;and will provide a framework to attain digital transformation on your business methodologies.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are your&nbsp;<strong>TECHNOLOGY APP PLATFORM PARTNER</strong>. Your way, anytime, anywhere.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is it available now?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Yes, join us and download apps! Sign up and share us with your friends and family.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><br /><br /></p>',
            ],
            [
                '' => 8,
                'name' => 'Disclaimer',
                'ja_name' => '免責事項',
                'pt_name' => 'Isenção de responsabilidade',
                'vi_name' => 'Tuyên bố miễn trừ trách nhiệm',
                'he_name' => 'כתב ויתור',
                'de_name' => 'Haftungsausschluss',
                'es_name' => 'Descargo de responsabilidad',
                'fr_name' => 'Clause de non-responsabilité',
                'ko_name' => '부인 성명',
                'zh_name' => '免责声明',
                'fil_name' => 'Disclaimer',
                'ar_name' => 'تنصل' ,
                'type' => 2,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Why us?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We&nbsp;<strong>CONNECT BUSINESSES</strong>&nbsp;with their customer base and help acquire new ones.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We build&nbsp;<strong>INNOVATIVE AND FULLY INTEGRATED SOLUTIONS</strong>&nbsp;to help increase our clients&rsquo; brand and enhance user experience.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are&nbsp;<strong>RESULT ORIENTED</strong>&nbsp;and will provide a framework to attain digital transformation on your business methodologies.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are your&nbsp;<strong>TECHNOLOGY APP PLATFORM PARTNER</strong>. Your way, anytime, anywhere.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is it available now?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Yes, join us and download apps! Sign up and share us with your friends and family.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><br /><br /></p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
               'ar_description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Why us?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We&nbsp;<strong>CONNECT BUSINESSES</strong>&nbsp;with their customer base and help acquire new ones.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We build&nbsp;<strong>INNOVATIVE AND FULLY INTEGRATED SOLUTIONS</strong>&nbsp;to help increase our clients&rsquo; brand and enhance user experience.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are&nbsp;<strong>RESULT ORIENTED</strong>&nbsp;and will provide a framework to attain digital transformation on your business methodologies.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are your&nbsp;<strong>TECHNOLOGY APP PLATFORM PARTNER</strong>. Your way, anytime, anywhere.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is it available now?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Yes, join us and download apps! Sign up and share us with your friends and family.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><br /><br /></p>',
            ],
            [
                'id' => 9,
                'name' => 'Privacy Policy',
                'ja_name' => 'プライバシーポリシー',
                'pt_name' => 'política de Privacidade',
                'vi_name' => 'Chính sách bảo mật',
                'he_name' => 'מדיניות פרטיות',
                'de_name' => 'Datenschutzrichtlinie',
                'es_name' => 'política de privacidad',
                'fr_name' => 'politique de confidentialité',
                'ko_name' => '개인정보 보호정책',
                'zh_name' => '隐私政策',
                'fil_name' => 'Patakaran sa Privacy',
                'ar_name' => 'سياسة الخصوصية' ,
                'type' => 2,
                'description' => '<header class="header" style="box-sizing: border-box; background-image: initial; background-position: initial; background-size: initial; background-repeat: initial; background-attachment: initial; background-origin: initial; background-clip: initial; display: inline-block; helvetica, arial, sans-serif; font-size: 18px;" data-darkreader-inline-bgimage="">
                    <div class="container" style="box-sizing: border-box; position: relative; max-width: 980px; margin-left: auto; margin-right: auto; padding-left: 20px; padding-right: 20px;">
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong style="font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Privacy Policy</span></strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Fixerity built the Fixerity  app as open source/free/freemium app. This SERVICE is provided by Fixerity and is intended for use as is.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This page is used to inform visitors regarding our policies with the collection, use, and disclosure of Personal Information if anyone decided to use [my/our] Service.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">If you choose to use our Service, then you agree to the collection and use of information in relation to this policy. The Personal Information that We collect is used for providing and improving the Service. We will not use or share your information with anyone except as described in this Privacy Policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">The terms used in this Privacy Policy have the same meanings as in our Terms and Conditions, which is accessible at Fixerity  unless otherwise defined in this Privacy Policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Information Collection and Use</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">For a better experience, while using our Service, We may require you to provide us with certain personally identifiable information add whatever else you collect here, e.g. users name, address, location, pictures The information that We request will be retained on your device and is not collected by us in any way/[retained by us and used as described in this privacy policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">The app does use third party services that may collect information used to identify you.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Link to privacy policy of third party service providers used by the app</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://www.google.com/policies/privacy">Google Play Services</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://firebase.google.com/policies/analytics">Google Analytics for Firebase</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://firebase.google.com/support/privacy/">Firebase Crashlytics</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Log Data</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We want to inform you that whenever you use our Service, in a case of an error in the app We collect data and information (through third party products) on your phone called Log Data. This Log Data may include information such as your device Internet Protocol (&ldquo;IP&rdquo;) address, device name, operating system version, the configuration of the app when utilizing [my/our] Service, the time and date of your use of the Service, and other statistics.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Cookies</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Cookies are files with a small amount of data that are commonly used as anonymous unique identifiers. These are sent to your browser from the websites that you visit and are stored on your device\'s internal memory.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This Service does not use these &ldquo;cookies&rdquo; explicitly. However, the app may use third party code and libraries that use &ldquo;cookies&rdquo; to collect information and improve their services. You have the option to either accept or refuse these cookies and know when a cookie is being sent to your device. If you choose to refuse our cookies, you may not be able to use some portions of this Service.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Service Providers</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We may employ third-party companies and individuals due to the following reasons:</p>
                    <ul style="color: #000000; font-size: medium; font-weight: 400;">
                    <li>To facilitate our Service;</li>
                    <li>To provide the Service on our behalf;</li>
                    <li>To perform Service-related services; or</li>
                    <li>To assist us in analyzing how our Service is used.</li>
                    </ul>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We want to inform users of this Service that these third parties have access to your Personal Information. The reason is to perform the tasks assigned to them on our behalf. However, they are obligated not to disclose or use the information for any other purpose.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Security</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it. But remember that no method of transmission over the internet, or method of electronic storage is 100% secure and reliable, and We cannot guarantee its absolute security.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Links to Other Sites</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This Service may contain links to other sites. If you click on a third-party link, you will be directed to that site. Note that these external sites are not operated by us. Therefore, We strongly advise you to review the Privacy Policy of these websites. We have no control over and assume no responsibility for the content, privacy policies, or practices of any third-party sites or services.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Children&rsquo;s Privacy</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">These Services do not address anyone under the age of 13. We do not knowingly collect personally identifiable information from children under 13. In the case We discover that a child under 13 has provided us with personal information, We immediately delete this from our servers. If you are a parent or guardian and you are aware that your child has provided us with personal information, please contact us so that We will be able to do necessary actions.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Changes to This Privacy Policy</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We may update our Privacy Policy from time to time. Thus, you are advised to review this page periodically for any changes. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Contact Us</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">If you have any questions or suggestions about our] Privacy Policy, do not hesitate to contact us at Whitelabelfoxapp@gmail.com.</p>
                    </div>
                    </header>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p><strong style="font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Privacy Policy</span></strong></p>
                    <p>Fixerity built the Fixerity  app as open source/free/freemium app. This SERVICE is provided by Fixerity and is intended for use as is.</p>
                    <p>This page is used to inform visitors regarding our policies with the collection, use, and disclosure of Personal Information if anyone decided to use [my/our] Service.</p>
                    <p>If you choose to use our Service, then you agree to the collection and use of information in relation to this policy. The Personal Information that We collect is used for providing and improving the Service. We will not use or share your information with anyone except as described in this Privacy Policy.</p>
                    <p>The terms used in this Privacy Policy have the same meanings as in our Terms and Conditions, which is accessible at Fixerity  unless otherwise defined in this Privacy Policy.</p>
                    <p><strong>Information Collection and Use</strong></p>
                    <p>For a better experience, while using our Service, We may require you to provide us with certain personally identifiable information add whatever else you collect here, e.g. users name, address, location, pictures The information that We request will be retained on your device and is not collected by us in any way/[retained by us and used as described in this privacy policy.</p>
                    <p>The app does use third party services that may collect information used to identify you.</p>
                    <p>Link to privacy policy of third party service providers used by the app</p>
                    <p><a href="https://www.google.com/policies/privacy">Google Play Services</a></p>
                    <p><a href="https://firebase.google.com/policies/analytics">Google Analytics for Firebase</a></p>
                    <p><a href="https://firebase.google.com/support/privacy/">Firebase Crashlytics</a></p>
                    <p><strong>Log Data</strong></p>
                    <p>We want to inform you that whenever you use our Service, in a case of an error in the app We collect data and information (through third party products) on your phone called Log Data. This Log Data may include information such as your device Internet Protocol (&ldquo;IP&rdquo;) address, device name, operating system version, the configuration of the app when utilizing [my/our] Service, the time and date of your use of the Service, and other statistics.</p>
                    <p><strong>Cookies</strong></p>
                    <p>Cookies are files with a small amount of data that are commonly used as anonymous unique identifiers. These are sent to your browser from the websites that you visit and are stored on your device\'s internal memory.</p>
                    <p>This Service does not use these &ldquo;cookies&rdquo; explicitly. However, the app may use third party code and libraries that use &ldquo;cookies&rdquo; to collect information and improve their services. You have the option to either accept or refuse these cookies and know when a cookie is being sent to your device. If you choose to refuse our cookies, you may not be able to use some portions of this Service.</p>
                    <p><strong>Service Providers</strong></p>
                    <p>We may employ third-party companies and individuals due to the following reasons:</p>
                    <ul>
                    <li>To facilitate our Service;</li>
                    <li>To provide the Service on our behalf;</li>
                    <li>To perform Service-related services; or</li>
                    <li>To assist us in analyzing how our Service is used.</li>
                    </ul>
                    <p>We want to inform users of this Service that these third parties have access to your Personal Information. The reason is to perform the tasks assigned to them on our behalf. However, they are obligated not to disclose or use the information for any other purpose.</p>
                    <p><strong>Security</strong></p>
                    <p>We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it. But remember that no method of transmission over the internet, or method of electronic storage is 100% secure and reliable, and We cannot guarantee its absolute security.</p>
                    <p><strong>Links to Other Sites</strong></p>
                    <p>This Service may contain links to other sites. If you click on a third-party link, you will be directed to that site. Note that these external sites are not operated by us. Therefore, We strongly advise you to review the Privacy Policy of these websites. We have no control over and assume no responsibility for the content, privacy policies, or practices of any third-party sites or services.</p>
                    <p><strong>Children&rsquo;s Privacy</strong></p>
                    <p>These Services do not address anyone under the age of 13. We do not knowingly collect personally identifiable information from children under 13. In the case We discover that a child under 13 has provided us with personal information, We immediately delete this from our servers. If you are a parent or guardian and you are aware that your child has provided us with personal information, please contact us so that We will be able to do necessary actions.</p>
                    <p><strong>Changes to This Privacy Policy</strong></p>
                    <p>We may update our Privacy Policy from time to time. Thus, you are advised to review this page periodically for any changes. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
                    <p><strong>Contact Us</strong></p>
                    <p>If you have any questions or suggestions about our] Privacy Policy, do not hesitate to contact us at Whitelabelfoxapp@gmail.com.</p>',
            ],
            [
                'id' => 10,
                'name' => 'Terms and Conditions',
                'ja_name' => '利用規約',
                'pt_name' => 'Termos e Condições',
                'vi_name' => 'Điều khoản và điều kiện',
                'he_name' => 'תנאים והגבלות',
                'de_name' => 'Geschäftsbedingungen',
                'es_name' => 'Términos y condiciones',
                'fr_name' => 'Termes et conditions',
                'ko_name' => '이용약관',
                'zh_name' => '条款和条件',
                'fil_name' => 'Mga Tuntunin at Kundisyon',
                'ar_name' => 'الأحكام والشروط' ,
                'type' => 2,
                'description' => '<p><strong>What personal information do we collect?</strong></p>
                    <p>When you place an order or complete a customer survey, we may collect personal information about you which may include name, email address, telephone number, location etc when voluntarily given by you. We collect this information to carry out the services offered by our app and to provide you offers and information about other services you may be interested in.</p>
                    <p>&nbsp;</p>
                    <p><strong>Who will see my personal information?</strong></p>
                    <p>Your privacy is of the utmost importance to us and no sensitive data will be shared without your consent.</p>
                    <p>&nbsp;</p>
                    <p><strong>Is my personal information secure with Fixerity ?</strong></p>
                    <p>Fixerity  will endeavor to protect your personal information from interference, modification, disclosure, misuse, loss, and unauthorized access. You are responsible for the confidentiality of your password and we strongly recommend against sharin</p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p><strong>What personal information do we collect?</strong></p>
                    <p>When you place an order or complete a customer survey, we may collect personal information about you which may include name, email address, telephone number, location etc when voluntarily given by you. We collect this information to carry out the services offered by our app and to provide you offers and information about other services you may be interested in.</p>
                    <p>&nbsp;</p>
                    <p><strong>Who will see my personal information?</strong></p>
                    <p>Your privacy is of the utmost importance to us and no sensitive data will be shared without your consent.</p>
                    <p>&nbsp;</p>
                    <p><strong>Is my personal information secure with Fixerity ?</strong></p>
                    <p>Fixerity  will endeavor to protect your personal information from interference, modification, disclosure, misuse, loss, and unauthorized access. You are responsible for the confidentiality of your password and we strongly recommend against sharin</p>',
            ],

            [
                'id' => 11,
                'name' => 'Contact us',
                'ja_name' => 'お問い合わせ',
                'pt_name' => 'Contate-nos',
                'vi_name' => 'Liên hệ với chúng tôi',
                'he_name' => 'צור איתנו קשר',
                'de_name' => 'Kontaktieren Sie uns',
                'es_name' => 'Contacta con nosotras',
                'fr_name' => 'Contactez-nous',
                'ko_name' => '문의하기',
                'zh_name' => '联系我们',
                'fil_name' => 'Makipag-ugnayan sa amin',
                'ar_name' => 'اتصل بنا' ,
                'type' => 3,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Media and Business Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">General and Technical Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">&nbsp;&nbsp;</span></p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Media and Business Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">General and Technical Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">&nbsp;&nbsp;</span></p>',
            ],
            [
                'id' => 12,
                'name' => 'FAQ',
                'ja_name' => 'よくある質問',
                'pt_name' => 'Perguntas frequentes',
                'vi_name' => 'Câu hỏi thường gặp',
                'he_name' => 'שאלות נפוצות',
                'de_name' => 'Häufig gestellte Fragen',
                'es_name' => 'Preguntas frecuentes',
                'fr_name' => 'FAQ',
                'ko_name' => '자주 묻는 질문',
                'zh_name' => '常问问题',
                'fil_name' => 'FAQ',
                'ar_name' => 'التعليمات' ,
                'type' => 3,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Why us?</span></strong></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Fixerity </span></strong><span style="font-family: Verdana, sans-serif;"> is a multi-service platform that provides an all-in-one solution to consumers and businesses.&nbsp;</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Fixerity </span></strong><span style="font-family: Verdana, sans-serif;"> will be a valued partner to our potential clients delivering turnkey solutions and measurable results.&nbsp;</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We&nbsp;<strong>CONNECT BUSINESSES</strong>&nbsp;with their customer base and help acquire new ones.</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We build&nbsp;<strong>INNOVATIVE AND FULLY INTEGRATED SOLUTIONS</strong>&nbsp;to help increase our clients&rsquo; brand and enhance user experience.</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are&nbsp;<strong>RESULT ORIENTED</strong>&nbsp;and will provide a framework to attain digital transformation on your business methodologies.</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are your&nbsp;<strong>TECHNOLOGY APP PLATFORM PARTNER</strong>. Your way, anytime, anywhere.</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                        <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is it available now?</span></strong></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Yes, join us and download apps! Sign up and share us with your friends and family.&nbsp;</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><br /><br /></p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Why us?</span></strong></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Fixerity </span></strong><span style="font-family: Verdana, sans-serif;"> is a multi-service platform that provides an all-in-one solution to consumers and businesses.&nbsp;</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Fixerity </span></strong><span style="font-family: Verdana, sans-serif;"> will be a valued partner to our potential clients delivering turnkey solutions and measurable results.&nbsp;</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We&nbsp;<strong>CONNECT BUSINESSES</strong>&nbsp;with their customer base and help acquire new ones.</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We build&nbsp;<strong>INNOVATIVE AND FULLY INTEGRATED SOLUTIONS</strong>&nbsp;to help increase our clients&rsquo; brand and enhance user experience.</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are&nbsp;<strong>RESULT ORIENTED</strong>&nbsp;and will provide a framework to attain digital transformation on your business methodologies.</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are your&nbsp;<strong>TECHNOLOGY APP PLATFORM PARTNER</strong>. Your way, anytime, anywhere.</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                        <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is it available now?</span></strong></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Yes, join us and download apps! Sign up and share us with your friends and family.&nbsp;</span></p>
                        <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><br /><br /></p>',
            ],
            [
                'id' => 13,
                'name' => 'Disclaimer',
                'ja_name' => '免責事項',
                'pt_name' => 'Isenção de responsabilidade',
                'vi_name' => 'Tuyên bố miễn trừ trách nhiệm',
                'he_name' => 'כתב ויתור',
                'de_name' => 'Haftungsausschluss',
                'es_name' => 'Descargo de responsabilidad',
                'fr_name' => 'Clause de non-responsabilité',
                'ko_name' => '부인 성명',
                'zh_name' => '免责声明',
                'fil_name' => 'Disclaimer',
                'ar_name' => 'تنصل' ,
                'type' => 3,
                'description' => '<p style="font-size: medium; font-weight: 400;">&nbsp;Disclaimer&nbsp;</p>
                    <p style="font-size: medium; font-weight: 400;">Please read this disclaimer ("disclaimer") carefully before using app (&ldquo;app&rdquo;,</p>
                    <p style="font-size: medium; font-weight: 400;">"service") operated by us.</p>
                    <p style="font-size: medium; font-weight: 400;">The content displayed on the app is the intellectual property of the app. You may not</p>
                    <p style="font-size: medium; font-weight: 400;">reuse, republish, or reprint such content without our written consent.</p>
                    <p style="font-size: medium; font-weight: 400;">All information posted is merely for educational and informational purposes. It is not intended</p>
                    <p style="font-size: medium; font-weight: 400;">as a substitute for professional advice. Should you decide to act upon any information on this</p>
                    <p style="font-size: medium; font-weight: 400;">app, you do so at your own risk.</p>
                    <p style="font-size: medium; font-weight: 400;">While the information on this app has been verified to the best of our abilities, we cannot</p>
                    <p style="font-size: medium; font-weight: 400;">guarantee that there are no mistakes or errors.</p>
                    <p style="font-size: medium; font-weight: 400;">We reserve the right to change this policy at any given time, of which you will be promptly</p>
                    <p style="font-size: medium; font-weight: 400;">updated. If you want to make sure that you are up to date with the latest changes, we advise</p>
                    <p style="font-size: medium; font-weight: 400;">you to frequently visit this page.</p>',
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p style="font-size: medium; font-weight: 400;">&nbsp;Disclaimer&nbsp;</p>
                <p style="font-size: medium; font-weight: 400;">Please read this disclaimer ("disclaimer") carefully before using app (&ldquo;app&rdquo;,</p>
                <p style="font-size: medium; font-weight: 400;">"service") operated by us.</p>
                <p style="font-size: medium; font-weight: 400;">The content displayed on the app is the intellectual property of the app. You may not</p>
                <p style="font-size: medium; font-weight: 400;">reuse, republish, or reprint such content without our written consent.</p>
                <p style="font-size: medium; font-weight: 400;">All information posted is merely for educational and informational purposes. It is not intended</p>
                <p style="font-size: medium; font-weight: 400;">as a substitute for professional advice. Should you decide to act upon any information on this</p>
                <p style="font-size: medium; font-weight: 400;">app, you do so at your own risk.</p>
                <p style="font-size: medium; font-weight: 400;">While the information on this app has been verified to the best of our abilities, we cannot</p>
                <p style="font-size: medium; font-weight: 400;">guarantee that there are no mistakes or errors.</p>
                <p style="font-size: medium; font-weight: 400;">We reserve the right to change this policy at any given time, of which you will be promptly</p>
                <p style="font-size: medium; font-weight: 400;">updated. If you want to make sure that you are up to date with the latest changes, we advise</p>
                <p style="font-size: medium; font-weight: 400;">you to frequently visit this page.</p>',
            ],
            [
                'id' => 14,
                'name' => 'Privacy Policy',
                'ja_name' => 'プライバシーポリシー',
                'pt_name' => 'política de Privacidade',
                'vi_name' => 'Chính sách bảo mật',
                'he_name' => 'מדיניות פרטיות',
                'de_name' => 'Datenschutzrichtlinie',
                'es_name' => 'política de privacidad',
                'fr_name' => 'politique de confidentialité',
                'ko_name' => '개인정보 보호정책',
                'zh_name' => '隐私政策',
                'fil_name' => 'Patakaran sa Privacy',
                'ar_name' => 'سياسة الخصوصية' ,
                'type' => 3,
                'description' => '<header class="header" style="box-sizing: border-box; background-image: initial; background-position: initial; background-size: initial; background-repeat: initial; background-attachment: initial; background-origin: initial; background-clip: initial; display: inline-block; width: 1903px; helvetica, arial, sans-serif; font-size: 18px;" data-darkreader-inline-bgimage="">
                    <div class="container" style="box-sizing: border-box; position: relative; max-width: 980px; margin-left: auto; margin-right: auto; padding-left: 20px; padding-right: 20px;">
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong style="font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Privacy Policy</span></strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Fixerity built the Fixerity  app as open source/free/freemium app. This SERVICE is provided by Fixerity and is intended for use as is.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This page is used to inform visitors regarding our policies with the collection, use, and disclosure of Personal Information if anyone decided to use [my/our] Service.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">If you choose to use our Service, then you agree to the collection and use of information in relation to this policy. The Personal Information that We collect is used for providing and improving the Service. We will not use or share your information with anyone except as described in this Privacy Policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">The terms used in this Privacy Policy have the same meanings as in our Terms and Conditions, which is accessible at Fixerity  unless otherwise defined in this Privacy Policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Information Collection and Use</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">For a better experience, while using our Service, We may require you to provide us with certain personally identifiable information add whatever else you collect here, e.g. users name, address, location, pictures The information that We request will be retained on your device and is not collected by us in any way/[retained by us and used as described in this privacy policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">The app does use third party services that may collect information used to identify you.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Link to privacy policy of third party service providers used by the app</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://www.google.com/policies/privacy">Google Play Services</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://firebase.google.com/policies/analytics">Google Analytics for Firebase</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://firebase.google.com/support/privacy/">Firebase Crashlytics</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Log Data</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We want to inform you that whenever you use our Service, in a case of an error in the app We collect data and information (through third party products) on your phone called Log Data. This Log Data may include information such as your device Internet Protocol (&ldquo;IP&rdquo;) address, device name, operating system version, the configuration of the app when utilizing [my/our] Service, the time and date of your use of the Service, and other statistics.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Cookies</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Cookies are files with a small amount of data that are commonly used as anonymous unique identifiers. These are sent to your browser from the websites that you visit and are stored on your device\'s internal memory.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This Service does not use these &ldquo;cookies&rdquo; explicitly. However, the app may use third party code and libraries that use &ldquo;cookies&rdquo; to collect information and improve their services. You have the option to either accept or refuse these cookies and know when a cookie is being sent to your device. If you choose to refuse our cookies, you may not be able to use some portions of this Service.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Service Providers</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We may employ third-party companies and individuals due to the following reasons:</p>
                    <ul style="color: #000000; font-size: medium; font-weight: 400;">
                    <li>To facilitate our Service;</li>
                    <li>To provide the Service on our behalf;</li>
                    <li>To perform Service-related services; or</li>
                    <li>To assist us in analyzing how our Service is used.</li>
                    </ul>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We want to inform users of this Service that these third parties have access to your Personal Information. The reason is to perform the tasks assigned to them on our behalf. However, they are obligated not to disclose or use the information for any other purpose.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Security</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it. But remember that no method of transmission over the internet, or method of electronic storage is 100% secure and reliable, and We cannot guarantee its absolute security.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Links to Other Sites</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This Service may contain links to other sites. If you click on a third-party link, you will be directed to that site. Note that these external sites are not operated by us. Therefore, We strongly advise you to review the Privacy Policy of these websites. We have no control over and assume no responsibility for the content, privacy policies, or practices of any third-party sites or services.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Children&rsquo;s Privacy</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">These Services do not address anyone under the age of 13. We do not knowingly collect personally identifiable information from children under 13. In the case We discover that a child under 13 has provided us with personal information, We immediately delete this from our servers. If you are a parent or guardian and you are aware that your child has provided us with personal information, please contact us so that We will be able to do necessary actions.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Changes to This Privacy Policy</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We may update our Privacy Policy from time to time. Thus, you are advised to review this page periodically for any changes. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Contact Us</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">If you have any questions or suggestions about our] Privacy Policy, do not hesitate to contact us at Whitelabelfoxapp@gmail.com.</p>
                    </div>
                    </header>',
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<header class="header" style="box-sizing: border-box; background-image: initial; background-position: initial; background-size: initial; background-repeat: initial; background-attachment: initial; background-origin: initial; background-clip: initial; display: inline-block; width: 1903px; helvetica, arial, sans-serif; font-size: 18px;" data-darkreader-inline-bgimage="">
                    <div class="container" style="box-sizing: border-box; position: relative; max-width: 980px; margin-left: auto; margin-right: auto; padding-left: 20px; padding-right: 20px;">
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong style="font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Privacy Policy</span></strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Fixerity built the Fixerity  app as open source/free/freemium app. This SERVICE is provided by Fixerity and is intended for use as is.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This page is used to inform visitors regarding our policies with the collection, use, and disclosure of Personal Information if anyone decided to use [my/our] Service.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">If you choose to use our Service, then you agree to the collection and use of information in relation to this policy. The Personal Information that We collect is used for providing and improving the Service. We will not use or share your information with anyone except as described in this Privacy Policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">The terms used in this Privacy Policy have the same meanings as in our Terms and Conditions, which is accessible at Fixerity  unless otherwise defined in this Privacy Policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Information Collection and Use</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">For a better experience, while using our Service, We may require you to provide us with certain personally identifiable information add whatever else you collect here, e.g. users name, address, location, pictures The information that We request will be retained on your device and is not collected by us in any way/[retained by us and used as described in this privacy policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">The app does use third party services that may collect information used to identify you.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Link to privacy policy of third party service providers used by the app</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://www.google.com/policies/privacy">Google Play Services</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://firebase.google.com/policies/analytics">Google Analytics for Firebase</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://firebase.google.com/support/privacy/">Firebase Crashlytics</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Log Data</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We want to inform you that whenever you use our Service, in a case of an error in the app We collect data and information (through third party products) on your phone called Log Data. This Log Data may include information such as your device Internet Protocol (&ldquo;IP&rdquo;) address, device name, operating system version, the configuration of the app when utilizing [my/our] Service, the time and date of your use of the Service, and other statistics.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Cookies</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Cookies are files with a small amount of data that are commonly used as anonymous unique identifiers. These are sent to your browser from the websites that you visit and are stored on your device\'s internal memory.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This Service does not use these &ldquo;cookies&rdquo; explicitly. However, the app may use third party code and libraries that use &ldquo;cookies&rdquo; to collect information and improve their services. You have the option to either accept or refuse these cookies and know when a cookie is being sent to your device. If you choose to refuse our cookies, you may not be able to use some portions of this Service.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Service Providers</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We may employ third-party companies and individuals due to the following reasons:</p>
                    <ul style="color: #000000; font-size: medium; font-weight: 400;">
                    <li>To facilitate our Service;</li>
                    <li>To provide the Service on our behalf;</li>
                    <li>To perform Service-related services; or</li>
                    <li>To assist us in analyzing how our Service is used.</li>
                    </ul>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We want to inform users of this Service that these third parties have access to your Personal Information. The reason is to perform the tasks assigned to them on our behalf. However, they are obligated not to disclose or use the information for any other purpose.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Security</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it. But remember that no method of transmission over the internet, or method of electronic storage is 100% secure and reliable, and We cannot guarantee its absolute security.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Links to Other Sites</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This Service may contain links to other sites. If you click on a third-party link, you will be directed to that site. Note that these external sites are not operated by us. Therefore, We strongly advise you to review the Privacy Policy of these websites. We have no control over and assume no responsibility for the content, privacy policies, or practices of any third-party sites or services.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Children&rsquo;s Privacy</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">These Services do not address anyone under the age of 13. We do not knowingly collect personally identifiable information from children under 13. In the case We discover that a child under 13 has provided us with personal information, We immediately delete this from our servers. If you are a parent or guardian and you are aware that your child has provided us with personal information, please contact us so that We will be able to do necessary actions.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Changes to This Privacy Policy</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We may update our Privacy Policy from time to time. Thus, you are advised to review this page periodically for any changes. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Contact Us</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">If you have any questions or suggestions about our] Privacy Policy, do not hesitate to contact us at Whitelabelfoxapp@gmail.com.</p>
                    </div>
                    </header>',
            ],
            [
                'id' => 15,
                'name' => 'Terms and Conditions',
                'ja_name' => '利用規約',
                'pt_name' => 'Termos e Condições',
                'vi_name' => 'Điều khoản và điều kiện',
                'he_name' => 'תנאים והגבלות',
                'de_name' => 'Geschäftsbedingungen',
                'es_name' => 'Términos y condiciones',
                'fr_name' => 'Termes et conditions',
                'ko_name' => '이용약관',
                'zh_name' => '条款和条件',
                'fil_name' => 'Mga Tuntunin at Kundisyon',
                'ar_name' => 'الأحكام والشروط' ,
                'type' => 3,
                'description' => '<p><strong>What personal information do we collect?</strong></p>
                    <p>When you place an order or complete a customer survey, we may collect personal information about you which may include name, email address, telephone number, location etc when voluntarily given by you. We collect this information to carry out the services offered by our app and to provide you offers and information about other services you may be interested in.</p>
                    <p>&nbsp;</p>
                    <p><strong>Who will see my personal information?</strong></p>
                    <p>Your privacy is of the utmost importance to us and no sensitive data will be shared without your consent.</p>
                    <p>&nbsp;</p>
                    <p><strong>Is my personal information secure with tow truck?</strong></p>
                    <p>tow truck will endeavor to protect your personal information from interference, modification, disclosure, misuse, loss, and unauthorized access. You are responsible for the confidentiality of your password and we strongly recommend against sharin</p>',
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p><strong>What personal information do we collect?</strong></p>
                    <p>When you place an order or complete a customer survey, we may collect personal information about you which may include name, email address, telephone number, location etc when voluntarily given by you. We collect this information to carry out the services offered by our app and to provide you offers and information about other services you may be interested in.</p>
                    <p>&nbsp;</p>
                    <p><strong>Who will see my personal information?</strong></p>
                    <p>Your privacy is of the utmost importance to us and no sensitive data will be shared without your consent.</p>
                    <p>&nbsp;</p>
                    <p><strong>Is my personal information secure with tow truck?</strong></p>
                    <p>tow truck will endeavor to protect your personal information from interference, modification, disclosure, misuse, loss, and unauthorized access. You are responsible for the confidentiality of your password and we strongly recommend against sharin</p>',
            ],

            [
                'id' => 16,
                'name' => 'Contact us',
                'ja_name' => 'お問い合わせ',
                'pt_name' => 'Contate-nos',
                'vi_name' => 'Liên hệ với chúng tôi',
                'he_name' => 'צור איתנו קשר',
                'de_name' => 'Kontaktieren Sie uns',
                'es_name' => 'Contacta con nosotras',
                'fr_name' => 'Contactez-nous',
                'ko_name' => '문의하기',
                'zh_name' => '联系我们',
                'fil_name' => 'Makipag-ugnayan sa amin',
                'ar_name' => 'اتصل بنا' ,
                'type' => 4,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Media and Business Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">General and Technical Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">&nbsp;&nbsp;</span></p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Media and Business Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">General and Technical Inquiries</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><span style="background-color: #ffffff; color: #333333; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;">Whitelabelfoxapp@gmail.com.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">&nbsp;&nbsp;</span></p>',
            ],
            [
                'id' => 17,
                'name' => 'FAQ',
                'ja_name' => 'よくある質問',
                'pt_name' => 'Perguntas frequentes',
                'vi_name' => 'Câu hỏi thường gặp',
                'he_name' => 'שאלות נפוצות',
                'de_name' => 'Häufig gestellte Fragen',
                'es_name' => 'Preguntas frecuentes',
                'fr_name' => 'FAQ',
                'ko_name' => '자주 묻는 질문',
                'zh_name' => '常问问题',
                'fil_name' => 'FAQ',
                'ar_name' => 'التعليمات' ,
                'type' => 4,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Why us?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Fixerity </span></strong><span style="font-family: Verdana, sans-serif;"> is a multi-service platform that provides an all-in-one solution to consumers and businesses.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Fixerity </span></strong><span style="font-family: Verdana, sans-serif;"> will be a valued partner to our potential clients delivering turnkey solutions and measurable results.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We&nbsp;<strong>CONNECT BUSINESSES</strong>&nbsp;with their customer base and help acquire new ones.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We build&nbsp;<strong>INNOVATIVE AND FULLY INTEGRATED SOLUTIONS</strong>&nbsp;to help increase our clients&rsquo; brand and enhance user experience.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are&nbsp;<strong>RESULT ORIENTED</strong>&nbsp;and will provide a framework to attain digital transformation on your business methodologies.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are your&nbsp;<strong>TECHNOLOGY APP PLATFORM PARTNER</strong>. Your way, anytime, anywhere.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is it available now?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Yes, join us and download apps! Sign up and share us with your friends and family.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><br /><br /></p>',
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Why us?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Fixerity </span></strong><span style="font-family: Verdana, sans-serif;"> is a multi-service platform that provides an all-in-one solution to consumers and businesses.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Fixerity </span></strong><span style="font-family: Verdana, sans-serif;"> will be a valued partner to our potential clients delivering turnkey solutions and measurable results.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We&nbsp;<strong>CONNECT BUSINESSES</strong>&nbsp;with their customer base and help acquire new ones.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We build&nbsp;<strong>INNOVATIVE AND FULLY INTEGRATED SOLUTIONS</strong>&nbsp;to help increase our clients&rsquo; brand and enhance user experience.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are&nbsp;<strong>RESULT ORIENTED</strong>&nbsp;and will provide a framework to attain digital transformation on your business methodologies.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are your&nbsp;<strong>TECHNOLOGY APP PLATFORM PARTNER</strong>. Your way, anytime, anywhere.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
                    <div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" data-darkreader-inline-color="" /></div>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is it available now?</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Yes, join us and download apps! Sign up and share us with your friends and family.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><br /><br /></p>',
            ],
            [
                'id' => 18,
                'name' => 'Disclaimer',
                'ja_name' => '免責事項',
                'pt_name' => 'Isenção de responsabilidade',
                'vi_name' => 'Tuyên bố miễn trừ trách nhiệm',
                'he_name' => 'כתב ויתור',
                'de_name' => 'Haftungsausschluss',
                'es_name' => 'Descargo de responsabilidad',
                'fr_name' => 'Clause de non-responsabilité',
                'ko_name' => '부인 성명',
                'zh_name' => '免责声明',
                'fil_name' => 'Disclaimer',
                'ar_name' => 'تنصل' ,
                'type' => 4,
                'description' => '<p>&nbsp;Disclaimer&nbsp;</p>
                    <p>Please read this disclaimer ("disclaimer") carefully before using app (&ldquo;app&rdquo;,</p>
                    <p>"service") operated by us.</p>
                    <p>The content displayed on the app is the intellectual property of the app. You may not</p>
                    <p>reuse, republish, or reprint such content without our written consent.</p>
                    <p>All information posted is merely for educational and informational purposes. It is not intended</p>
                    <p>as a substitute for professional advice. Should you decide to act upon any information on this</p>
                    <p>app, you do so at your own risk.</p>
                    <p>While the information on this app has been verified to the best of our abilities, we cannot</p>
                    <p>guarantee that there are no mistakes or errors.</p>
                    <p>We reserve the right to change this policy at any given time, of which you will be promptly</p>
                    <p>updated. If you want to make sure that you are up to date with the latest changes, we advise</p>
                    <p>you to frequently visit this page.</p>',
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p>&nbsp;Disclaimer&nbsp;</p>
                    <p>Please read this disclaimer ("disclaimer") carefully before using app (&ldquo;app&rdquo;,</p>
                    <p>"service") operated by us.</p>
                    <p>The content displayed on the app is the intellectual property of the app. You may not</p>
                    <p>reuse, republish, or reprint such content without our written consent.</p>
                    <p>All information posted is merely for educational and informational purposes. It is not intended</p>
                    <p>as a substitute for professional advice. Should you decide to act upon any information on this</p>
                    <p>app, you do so at your own risk.</p>
                    <p>While the information on this app has been verified to the best of our abilities, we cannot</p>
                    <p>guarantee that there are no mistakes or errors.</p>
                    <p>We reserve the right to change this policy at any given time, of which you will be promptly</p>
                    <p>updated. If you want to make sure that you are up to date with the latest changes, we advise</p>
                    <p>you to frequently visit this page.</p>',
            ],
            [
                'id' => 19,
                'name' => 'Privacy Policy',
                'ja_name' => 'プライバシーポリシー',
                'pt_name' => 'política de Privacidade',
                'vi_name' => 'Chính sách bảo mật',
                'he_name' => 'מדיניות פרטיות',
                'de_name' => 'Datenschutzrichtlinie',
                'es_name' => 'política de privacidad',
                'fr_name' => 'politique de confidentialité',
                'ko_name' => '개인정보 보호정책',
                'zh_name' => '隐私政策',
                'fil_name' => 'Patakaran sa Privacy',
                'ar_name' => 'سياسة الخصوصية' ,
                'type' => 4,
                'description' => '<header class="header" style="box-sizing: border-box; background-image: initial; background-position: initial; background-size: initial; background-repeat: initial; background-attachment: initial; background-origin: initial; background-clip: initial; display: inline-block; width: 1903px; helvetica, arial, sans-serif; font-size: 18px;" data-darkreader-inline-bgimage="">
                    <div class="container" style="box-sizing: border-box; position: relative; max-width: 980px; margin-left: auto; margin-right: auto; padding-left: 20px; padding-right: 20px;">
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong style="font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Privacy Policy</span></strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Fixerity built the Fixerity  app as open source/free/freemium app. This SERVICE is provided by Fixerity and is intended for use as is.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This page is used to inform visitors regarding our policies with the collection, use, and disclosure of Personal Information if anyone decided to use [my/our] Service.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">If you choose to use our Service, then you agree to the collection and use of information in relation to this policy. The Personal Information that We collect is used for providing and improving the Service. We will not use or share your information with anyone except as described in this Privacy Policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">The terms used in this Privacy Policy have the same meanings as in our Terms and Conditions, which is accessible at Fixerity  unless otherwise defined in this Privacy Policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Information Collection and Use</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">For a better experience, while using our Service, We may require you to provide us with certain personally identifiable information add whatever else you collect here, e.g. users name, address, location, pictures The information that We request will be retained on your device and is not collected by us in any way/[retained by us and used as described in this privacy policy.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">The app does use third party services that may collect information used to identify you.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Link to privacy policy of third party service providers used by the app</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://www.google.com/policies/privacy">Google Play Services</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://firebase.google.com/policies/analytics">Google Analytics for Firebase</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><a href="https://firebase.google.com/support/privacy/">Firebase Crashlytics</a></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Log Data</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We want to inform you that whenever you use our Service, in a case of an error in the app We collect data and information (through third party products) on your phone called Log Data. This Log Data may include information such as your device Internet Protocol (&ldquo;IP&rdquo;) address, device name, operating system version, the configuration of the app when utilizing [my/our] Service, the time and date of your use of the Service, and other statistics.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Cookies</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">Cookies are files with a small amount of data that are commonly used as anonymous unique identifiers. These are sent to your browser from the websites that you visit and are stored on your device\'s internal memory.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This Service does not use these &ldquo;cookies&rdquo; explicitly. However, the app may use third party code and libraries that use &ldquo;cookies&rdquo; to collect information and improve their services. You have the option to either accept or refuse these cookies and know when a cookie is being sent to your device. If you choose to refuse our cookies, you may not be able to use some portions of this Service.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Service Providers</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We may employ third-party companies and individuals due to the following reasons:</p>
                    <ul style="color: #000000; font-size: medium; font-weight: 400;">
                    <li>To facilitate our Service;</li>
                    <li>To provide the Service on our behalf;</li>
                    <li>To perform Service-related services; or</li>
                    <li>To assist us in analyzing how our Service is used.</li>
                    </ul>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We want to inform users of this Service that these third parties have access to your Personal Information. The reason is to perform the tasks assigned to them on our behalf. However, they are obligated not to disclose or use the information for any other purpose.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Security</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it. But remember that no method of transmission over the internet, or method of electronic storage is 100% secure and reliable, and We cannot guarantee its absolute security.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Links to Other Sites</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">This Service may contain links to other sites. If you click on a third-party link, you will be directed to that site. Note that these external sites are not operated by us. Therefore, We strongly advise you to review the Privacy Policy of these websites. We have no control over and assume no responsibility for the content, privacy policies, or practices of any third-party sites or services.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Children&rsquo;s Privacy</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">These Services do not address anyone under the age of 13. We do not knowingly collect personally identifiable information from children under 13. In the case We discover that a child under 13 has provided us with personal information, We immediately delete this from our servers. If you are a parent or guardian and you are aware that your child has provided us with personal information, please contact us so that We will be able to do necessary actions.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Changes to This Privacy Policy</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">We may update our Privacy Policy from time to time. Thus, you are advised to review this page periodically for any changes. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;"><strong>Contact Us</strong></p>
                    <p style="color: #000000; font-size: medium; font-weight: 400;">If you have any questions or suggestions about our] Privacy Policy, do not hesitate to contact us at Whitelabelfoxapp@gmail.com.</p>
                    </div>
                    </header>',
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p><strong style="font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Privacy Policy</span></strong></p>
                    <p>Fixerity built the Fixerity  app as open source/free/freemium app. This SERVICE is provided by Fixerity and is intended for use as is.</p>
                    <p>This page is used to inform visitors regarding our policies with the collection, use, and disclosure of Personal Information if anyone decided to use [my/our] Service.</p>
                    <p>If you choose to use our Service, then you agree to the collection and use of information in relation to this policy. The Personal Information that We collect is used for providing and improving the Service. We will not use or share your information with anyone except as described in this Privacy Policy.</p>
                    <p>The terms used in this Privacy Policy have the same meanings as in our Terms and Conditions, which is accessible at Fixerity  unless otherwise defined in this Privacy Policy.</p>
                    <p><strong>Information Collection and Use</strong></p>
                    <p>For a better experience, while using our Service, We may require you to provide us with certain personally identifiable information add whatever else you collect here, e.g. users name, address, location, pictures The information that We request will be retained on your device and is not collected by us in any way/[retained by us and used as described in this privacy policy.</p>
                    <p>The app does use third party services that may collect information used to identify you.</p>
                    <p>Link to privacy policy of third party service providers used by the app</p>
                    <p><a href="https://www.google.com/policies/privacy">Google Play Services</a></p>
                    <p><a href="https://firebase.google.com/policies/analytics">Google Analytics for Firebase</a></p>
                    <p><a href="https://firebase.google.com/support/privacy/">Firebase Crashlytics</a></p>
                    <p><strong>Log Data</strong></p>
                    <p>We want to inform you that whenever you use our Service, in a case of an error in the app We collect data and information (through third party products) on your phone called Log Data. This Log Data may include information such as your device Internet Protocol (&ldquo;IP&rdquo;) address, device name, operating system version, the configuration of the app when utilizing [my/our] Service, the time and date of your use of the Service, and other statistics.</p>
                    <p><strong>Cookies</strong></p>
                    <p>Cookies are files with a small amount of data that are commonly used as anonymous unique identifiers. These are sent to your browser from the websites that you visit and are stored on your device\'s internal memory.</p>
                    <p>This Service does not use these &ldquo;cookies&rdquo; explicitly. However, the app may use third party code and libraries that use &ldquo;cookies&rdquo; to collect information and improve their services. You have the option to either accept or refuse these cookies and know when a cookie is being sent to your device. If you choose to refuse our cookies, you may not be able to use some portions of this Service.</p>
                    <p><strong>Service Providers</strong></p>
                    <p>We may employ third-party companies and individuals due to the following reasons:</p>
                    <ul>
                    <li>To facilitate our Service;</li>
                    <li>To provide the Service on our behalf;</li>
                    <li>To perform Service-related services; or</li>
                    <li>To assist us in analyzing how our Service is used.</li>
                    </ul>
                    <p>We want to inform users of this Service that these third parties have access to your Personal Information. The reason is to perform the tasks assigned to them on our behalf. However, they are obligated not to disclose or use the information for any other purpose.</p>
                    <p><strong>Security</strong></p>
                    <p>We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it. But remember that no method of transmission over the internet, or method of electronic storage is 100% secure and reliable, and We cannot guarantee its absolute security.</p>
                    <p><strong>Links to Other Sites</strong></p>
                    <p>This Service may contain links to other sites. If you click on a third-party link, you will be directed to that site. Note that these external sites are not operated by us. Therefore, We strongly advise you to review the Privacy Policy of these websites. We have no control over and assume no responsibility for the content, privacy policies, or practices of any third-party sites or services.</p>
                    <p><strong>Children&rsquo;s Privacy</strong></p>
                    <p>These Services do not address anyone under the age of 13. We do not knowingly collect personally identifiable information from children under 13. In the case We discover that a child under 13 has provided us with personal information, We immediately delete this from our servers. If you are a parent or guardian and you are aware that your child has provided us with personal information, please contact us so that We will be able to do necessary actions.</p>
                    <p><strong>Changes to This Privacy Policy</strong></p>
                    <p>We may update our Privacy Policy from time to time. Thus, you are advised to review this page periodically for any changes. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
                    <p><strong>Contact Us</strong></p>
                    <p>If you have any questions or suggestions about our] Privacy Policy, do not hesitate to contact us at Whitelabelfoxapp@gmail.com.</p>',
            ],
            [
                'id' => 20,
                'name' => 'Terms and Conditions',
                'ja_name' => '利用規約',
                'pt_name' => 'Termos e Condições',
                'vi_name' => 'Điều khoản và điều kiện',
                'he_name' => 'תנאים והגבלות',
                'de_name' => 'Geschäftsbedingungen',
                'es_name' => 'Términos y condiciones',
                'fr_name' => 'Termes et conditions',
                'ko_name' => '이용약관',
                'zh_name' => '条款和条件',
                'fil_name' => 'Mga Tuntunin at Kundisyon',
                'ar_name' => 'الأحكام والشروط' ,
                'type' => 4,
                'description' => '<p><strong>What personal information do we collect?</strong></p>
                    <p>When you place an order or complete a customer survey, we may collect personal information about you which may include name, email address, telephone number, location etc when voluntarily given by you. We collect this information to carry out the services offered by our app and to provide you offers and information about other services you may be interested in.</p>
                    <p>&nbsp;</p>
                    <p><strong>Who will see my personal information?</strong></p>
                    <p>Your privacy is of the utmost importance to us and no sensitive data will be shared without your consent.</p>
                    <p>&nbsp;</p>
                    <p><strong>Is my personal information secure with tow truck?</strong></p>
                    <p>tow truck will endeavor to protect your personal information from interference, modification, disclosure, misuse, loss, and unauthorized access. You are responsible for the confidentiality of your password and we strongly recommend against sharin</p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p><strong>What personal information do we collect?</strong></p>
                    <p>When you place an order or complete a customer survey, we may collect personal information about you which may include name, email address, telephone number, location etc when voluntarily given by you. We collect this information to carry out the services offered by our app and to provide you offers and information about other services you may be interested in.</p>
                    <p>&nbsp;</p>
                    <p><strong>Who will see my personal information?</strong></p>
                    <p>Your privacy is of the utmost importance to us and no sensitive data will be shared without your consent.</p>
                    <p>&nbsp;</p>
                    <p><strong>Is my personal information secure with tow truck?</strong></p>
                    <p>tow truck will endeavor to protect your personal information from interference, modification, disclosure, misuse, loss, and unauthorized access. You are responsible for the confidentiality of your password and we strongly recommend against sharin</p>',
            ],

            [
                'id' => 21,
                'name' => 'Contact us',
                'ja_name' => 'お問い合わせ',
                'pt_name' => 'Contate-nos',
                'vi_name' => 'Liên hệ với chúng tôi',
                'he_name' => 'צור איתנו קשר',
                'de_name' => 'Kontaktieren Sie uns',
                'es_name' => 'Contacta con nosotras',
                'fr_name' => 'Contactez-nous',
                'ko_name' => '문의하기',
                'zh_name' => '联系我们',
                'fil_name' => 'Makipag-ugnayan sa amin',
                'ar_name' => Null,
                'type' => 5,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">General and Technical Inquiries</span></strong></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><a href="mailto:help@my-checkout.com"><span style="font-family: Verdana, sans-serif; color: rgb(0, 112, 192);">help@my-checkout.com</span></a></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: rgb(160, 160, 160);" align="center" noshade="noshade" size="1" width="100%" /></div>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">&nbsp;&nbsp;</span></p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => Null,
            ],
            [
                'id' => 22,
                'name' => 'FAQ',
                'ja_name' => 'よくある質問',
                'pt_name' => 'Perguntas frequentes',
                'vi_name' => 'Câu hỏi thường gặp',
                'he_name' => 'שאלות נפוצות',
                'de_name' => 'Häufig gestellte Fragen',
                'es_name' => 'Preguntas frecuentes',
                'fr_name' => 'FAQ',
                'ko_name' => '자주 묻는 질문',
                'zh_name' => '常问问题',
                'fil_name' => 'FAQ',
                'ar_name' => Null ,
                'type' => 5,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Why us?</span></strong></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">MY-CHECKOUT</span></strong><span style="font-family: Verdana, sans-serif;">&nbsp;is a multi-service platform that provides an all-in-one solution to consumers and businesses.&nbsp;</span></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">MY-CHECKOUT</span></strong><span style="font-family: Verdana, sans-serif;">&nbsp;will be a valued partner to our potential clients delivering turnkey solutions and measurable results.&nbsp;</span></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We&nbsp;<strong>CONNECT BUSINESSES</strong>&nbsp;with their customer base and help acquire new ones.</span></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We build&nbsp;<strong>INNOVATIVE AND FULLY INTEGRATED SOLUTIONS</strong>&nbsp;to help increase our clients&rsquo; brand and enhance user experience.</span></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are&nbsp;<strong>RESULT ORIENTED</strong>&nbsp;and will provide a framework to attain digital transformation on your business methodologies.</span></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;"><span style="font-family: Wingdings;">+</span><span style="font-family: \'Times New Roman\', serif;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">We are your&nbsp;<strong>TECHNOLOGY APP PLATFORM PARTNER</strong>. Your way, anytime, anywhere.</span></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt 0.5in; font-size: medium; font-family: Calibri, sans-serif; text-indent: -0.25in;">&nbsp;</p>
<div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" /></div>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is it available now?</span></strong></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Yes, join us and download apps! Sign up and share us with your friends and family.&nbsp;</span></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Apple App Store</span></strong></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;"><a href="http://b.link/appstore-myCheckout"><span style="color: #0070c0;">http://b.link/appstore-myCheckout</span></a></span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;">&nbsp;</span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;"><a href="http://b.link/appstore-myService"><span style="color: #0070c0;">http://b.link/appstore-myService</span></a></span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;">&nbsp;</span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;"><a href="http://b.link/appstore-myStore"><span style="color: #0070c0;">http://b.link/appstore-myStore</span></a></span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;">&nbsp;</span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;"><a href="http://b.link/appstore-myShopper"><span style="color: #0070c0;">http://b.link/appstore-myShopper</span></a></span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;">&nbsp;</span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;"><a href="http://b.link/appstore-myDriver"><span style="color: #0070c0;">http://b.link/appstore-myDriver</span></a></span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif; color: #0070c0;">&nbsp;</span></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Google Play Store</span></strong></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;"><a href="http://b.link/googleplay-myCheckout"><span style="color: #0070c0;">http://b.link/googleplay-myCheckout</span></a></span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;">&nbsp;</span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;"><a href="http://b.link/googleplay-myService"><span style="color: #0070c0;">http://b.link/googleplay-myService</span></a></span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;">&nbsp;</span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;"><a href="http://b.link/googleplay-myStore"><span style="color: #0070c0;">http://b.link/googleplay-myStore</span></a></span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;">&nbsp;</span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;"><a href="http://b.link/googleplay-myShopper"><span style="color: #0070c0;">http://b.link/googleplay-myShopper</span></a></span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;">&nbsp;</span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><u><span style="font-family: Verdana, sans-serif; color: #0070c0;"><a href="http://b.link/googleplay-myDriver"><span style="color: #0070c0;">http://b.link/googleplay-myDriver</span></a></span></u></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" /></div>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">Is there a Help Center?</span></strong></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Visit our&nbsp;</span><a title="Help Center" href="https://sites.google.com/view/my-checkout/home"><span style="font-family: Verdana, sans-serif; color: #0070c0;">Wiki Help Center</span></a><span style="font-family: Verdana, sans-serif; color: #0070c0;">&nbsp;</span><span style="font-family: Verdana, sans-serif;">for details</span></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" /></div>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><strong><span style="font-family: Verdana, sans-serif;">General and Technical Inquiries</span></strong></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">Email us:&nbsp;</span><a href="mailto:help@my-checkout.com"><span style="font-family: Verdana, sans-serif; color: #0070c0;">help@my-checkout.com</span></a></p>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;">&nbsp;</p>
<div class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; text-align: center;" align="center"><hr style="color: #a0a0a0;" align="center" noshade="noshade" size="1" width="100%" /></div>
<p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif;"><span style="font-family: Verdana, sans-serif;">&nbsp;&nbsp;</span></p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => Null,
            ],
            [
                'id' => 23,
                'name' => 'Disclaimer',
                'ja_name' => '免責事項',
                'pt_name' => 'Isenção de responsabilidade',
                'vi_name' => 'Tuyên bố miễn trừ trách nhiệm',
                'he_name' => 'כתב ויתור',
                'de_name' => 'Haftungsausschluss',
                'es_name' => 'Descargo de responsabilidad',
                'fr_name' => 'Clause de non-responsabilité',
                'ko_name' => '부인 성명',
                'zh_name' => '免责声明',
                'fil_name' => 'Disclaimer',
                'ar_name' => 'Ar Disclaimer' ,
                'type' => 5,
                'description' => '<h3 style="text-align: left;"><strong style="font-family: Calibri, sans-serif; font-size: medium; text-align: justify;"><span style="font-size: 10pt;">Disclaimer</span></strong></h3>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p><strong style="font-family: Calibri, sans-serif; text-align: justify;"><span style="font-size: 10pt;">Disclaimer</span></strong></p>',
            ],
            [
                'id' => 24,
                'name' => 'Privacy Policy',
                'ja_name' => 'プライバシーポリシー',
                'pt_name' => 'política de Privacidade',
                'vi_name' => 'Chính sách bảo mật',
                'he_name' => 'מדיניות פרטיות',
                'de_name' => 'Datenschutzrichtlinie',
                'es_name' => 'política de privacidad',
                'fr_name' => 'politique de confidentialité',
                'ko_name' => '개인정보 보호정책',
                'zh_name' => '隐私政策',
                'fil_name' => 'Patakaran sa Privacy',
                'ar_name' => Null,
                'type' => 5,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 12.75pt; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><strong><span style="font-size: 10pt;">Privacy Policy</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 12.75pt; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">My-Checkout Corp&nbsp;(&ldquo;we&rdquo; or &ldquo;us&rdquo; or &ldquo;our&rdquo;) respects the privacy of our users (&ldquo;user&rdquo; or &ldquo;you&rdquo;). This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our mobile application (the &ldquo;Application&rdquo;).&nbsp;Please read this Privacy Policy carefully. IF YOU DO NOT AGREE WITH THE TERMS OF THIS PRIVACY POLICY, PLEASE DO NOT ACCESS THE APPLICATION.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We reserve the right to make changes to this Privacy Policy at any time and for any reason. We will alert you about any changes by updating the &ldquo;Last updated&rdquo; date of this Privacy Policy. You are encouraged to periodically review this Privacy Policy to stay informed of updates. You will be deemed to have been made aware of, will be subject to, and will be deemed to have accepted the changes in any revised Privacy Policy by your continued use of the Application after the date such revised Privacy Policy is posted.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><strong><span style="font-size: 10pt;">Collection of Information</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We may collect information about you in a variety of ways. The information we may collect via the Application depends on the content and materials you use, and includes:</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Personal Data</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Demographic and other personally identifiable information (such as your name, mobile number and email address) that you voluntarily give to us when choosing to participate in various activities related to the Application, such as service offerings, location, liking posts, sending feedback, and responding to surveys. If you choose to share data about yourself via your profile, online chat, or other interactive areas of the Application, please be advised that all data you disclose in these areas is public and your data will be accessible to anyone who accesses the Application.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Derivative Data</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Information our servers automatically collect when you access the Application, such as your native actions that are integral to the Application as well as other interactions with the Application and other users via server log files.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Financial Data</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We store only very limited, if any, basic information that we collect pertaining to your purchase (such as amount, payment method, transaction date, etc.). Otherwise, all financial information is stored by our payment processor,&nbsp;[</span><span style="font-size: 10pt;">PayPal, Paymongo, Stripe and other approved payment gateway], and you are encouraged to review their privacy policy and contact them directly for responses to your questions.&nbsp;&nbsp;If we add a new payment gateway, we will update the document for your reference.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 12pt; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Facebook Permissions</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">The Application may by default access your&nbsp;</span><a style="color: #954f72;" href="https://www.facebook.com/about/privacy/"><span style="font-size: 10pt; color: windowtext; text-decoration: none;">Facebook</span></a><span style="font-size: 10pt;">&nbsp;basic account information, including your name, email, and profile picture URL, as well as other information that you choose to make public. For more information regarding Facebook permissions, refer to the&nbsp;</span><a style="color: #954f72;" href="https://developers.facebook.com/docs/facebook-login/permissions"><span style="font-size: 10pt; color: windowtext; text-decoration: none;">Facebook Permissions Reference</span></a><span style="font-size: 10pt;">&nbsp;page.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Geo-Location Information</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We may request access or permission to and track location-based information from your mobile device, either continuously or while you are using the Application, to provide location-based services. If you wish to change our access or permissions, you may do so in your device&rsquo;s settings.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Mobile Device Data</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Device information such as your mobile device ID number, model, and manufacturer, version of your operating system, phone number, country, location, and any other data you choose to provide.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Third-Party Data</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Information from third parties, such as personal information or network friends, if you connect your account to the third party and grant the Application permission to access this information.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Data from Contests, Giveaways, and Surveys</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Personal and other information you may provide when entering contests or giveaways and/or responding to surveys.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><strong><span style="font-size: 10pt;">Use of Information</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Having accurate information about you permits us to provide you with a smooth, efficient, and customized experience. Specifically, we may use information collected about you via the Application to:</span></p>
                    <p class="MsoListParagraphCxSpFirst" style="margin-left: 0.25in; text-indent: -0.25in; text-align: justify;"><span style="font-size: 10pt;">1.<span style="font-stretch: normal; font-size: 7pt; line-height: normal; font-family: \'Times New Roman\';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span><span style="font-size: 10pt;">Email you regarding your account or order.</span></p>
                    <p class="MsoListParagraphCxSpMiddle" style="margin-left: 0.25in; text-indent: -0.25in; text-align: justify;"><span style="font-size: 10pt;">2.<span style="font-stretch: normal; font-size: 7pt; line-height: normal; font-family: \'Times New Roman\';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span><span style="font-size: 10pt;">Enable user-to-user communications.</span></p>
                    <p class="MsoListParagraphCxSpMiddle" style="margin-left: 0.25in; text-indent: -0.25in; text-align: justify;"><span style="font-size: 10pt;">3.<span style="font-stretch: normal; font-size: 7pt; line-height: normal; font-family: \'Times New Roman\';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span><span style="font-size: 10pt;">Fulfill and manage purchases, orders, payments, and other transactions related to the Application.</span></p>
                    <p class="MsoListParagraphCxSpMiddle" style="margin-left: 0.25in; text-indent: -0.25in; text-align: justify;"><span style="font-size: 10pt;">4.<span style="font-stretch: normal; font-size: 7pt; line-height: normal; font-family: \'Times New Roman\';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span><span style="font-size: 10pt;">Process payments and refunds.</span></p>
                    <p class="MsoListParagraphCxSpMiddle" style="margin-left: 0.25in; text-indent: -0.25in; text-align: justify;"><span style="font-size: 10pt;">5.<span style="font-stretch: normal; font-size: 7pt; line-height: normal; font-family: \'Times New Roman\';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span><span style="font-size: 10pt;">Request feedback and contact you about your use of the Application.</span></p>
                    <p class="MsoListParagraphCxSpMiddle" style="margin-left: 0.25in; text-indent: -0.25in; text-align: justify;"><span style="font-size: 10pt;">6.<span style="font-stretch: normal; font-size: 7pt; line-height: normal; font-family: \'Times New Roman\';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span><span style="font-size: 10pt;">Resolve disputes and troubleshoot problems.</span></p>
                    <p class="MsoListParagraphCxSpLast" style="margin-left: 0.25in; text-indent: -0.25in; text-align: justify;"><span style="font-size: 10pt;">7.<span style="font-stretch: normal; font-size: 7pt; line-height: normal; font-family: \'Times New Roman\';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span><span style="font-size: 10pt;">Respond to product and customer service requests.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><strong><span style="font-size: 10pt;">&nbsp;</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><strong><span style="font-size: 10pt;">Disclosure of Information</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We may share information we have collected about you in certain situations. Your information may be disclosed as follows:</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">By Law or to Protect Rights</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">If we believe the release of information about you is necessary to respond to legal process, to investigate or remedy potential violations of our policies, or to protect the rights, property, and safety of others, we may share your information as permitted or required by any applicable law, rule, or regulation. This includes exchanging information with other entities for fraud protection and credit risk reduction.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Third-Party Service Providers</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We may share your information with third parties that perform services for us or on our behalf, including payment processing, email delivery, hosting services, customer service, and marketing assistance.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Marketing Communications</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">With your consent, or with an opportunity for you to withdraw consent, we may share your information with third parties for marketing purposes, as permitted by law.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Interactions with Other Users</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">If you interact with other users of the Application, those users may see your name, profile photo, and descriptions of your activity, including sending invitations to other users, chatting with other users, liking posts, following blogs.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Online Postings</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">When you post comments, contributions or other content to the Applications, your posts may be viewed by all users and may be publicly distributed outside the Application in perpetuity</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Third-Party Advertisers</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We may use third-party advertising companies to serve ads when you visit the Application. These companies may use information about your visits to the Application and other websites that are contained in web cookies in order to provide advertisements about goods and services of interest to you.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Affiliates</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We may share your information with our affiliates, in which case we will require those affiliates to honor this Privacy Policy. Affiliates include our parent company and any subsidiaries, joint venture partners or other companies that we control or that are under common control with us.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Business Partners</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We may share your information with our business partners to offer you certain products, services or promotions.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Offer Wall</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">The Application may display a third-party-hosted &ldquo;offer wall.&rdquo; Such an offer wall allows third-party advertisers to offer virtual currency, gifts, or other items to users in return for acceptance and completion of an advertisement offer. Such an offer wall may appear in the Application and be displayed to you based on certain data, such as your geographic area or demographic information. When you click on an offer wall, you will leave the Application. A unique identifier, such as your user ID, will be shared with the offer wall provider in order to prevent fraud and properly credit your account.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Social Media Contacts</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">If you connect to the Application through a social network, your contacts on the social network will see your name, profile photo, and descriptions of your activity.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Other Third Parties</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We may share your information with advertisers and investors for the purpose of conducting general business analysis. We may also share your information with such third parties for marketing purposes, as permitted by law.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Sale or Bankruptcy</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">If we reorganize or sell all or a portion of our assets, undergo a merger, or are acquired by another entity, we may transfer your information to the successor entity. If we go out of business or enter bankruptcy, your information would be an asset transferred or acquired by a third party. You acknowledge that such transfers may occur and that the transferee may decline honor commitments we made in this Privacy Policy.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We are not responsible for the actions of third parties with whom you share personal or sensitive data, and we have no authority to manage or control third-party solicitations. If you no longer wish to receive correspondence, emails or other communications from third parties, you are responsible for contacting the third party directly.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Account Termination</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">You may at any time terminate your account by sending us an email to&nbsp;</span><a style="color: #954f72;" href="mailto:help@my-checkout.com"><span style="font-size: 10pt;">help@my-checkout.com</span></a><span style="font-size: 10pt;">&nbsp;with a Subject line of &ldquo;Delete my account (first name last name) from the app&rdquo;. Upon your request to terminate your account, we will deactivate or delete your account and information from our active databases. However, some information may be retained in our files to prevent fraud, troubleshoot problems, assist with any investigations, enforce our Terms of Use and/or comply with legal requirements.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><strong><span style="font-size: 10pt;">Tracking Technologies</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Cookies and Web Beacons</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We may use cookies, web beacons, tracking pixels, and other tracking technologies on the Application to help customize the Application and improve your experience. When you access the Application, your personal information is not collected through the use of tracking technology. Most browsers are set to accept cookies by default. You can remove or reject cookies, but be aware that such action could affect the availability and functionality of the Application. You may not decline web beacons. However, they can be rendered ineffective by declining all cookies or by modifying your web browser&rsquo;s settings to notify you each time a cookie is tendered, permitting you to accept or decline cookies on an individual basis.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Internet-Based Advertising</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Additionally, we may use third-party software to serve ads on the Application, implement email marketing campaigns, and manage other interactive marketing initiatives. This third-party software may use cookies or similar tracking technology to help manage and optimize your online experience with us. For more information about opting-out of interest-based ads, visit the&nbsp;</span><a style="color: #954f72;" href="http://optout.networkadvertising.org/?c=1"><span style="font-size: 10pt; color: windowtext; text-decoration: none;">Network Advertising Initiative Opt-Out Tool</span></a><span style="font-size: 10pt;">&nbsp;or&nbsp;</span><a style="color: #954f72;" href="http://www.aboutads.info/choices/"><span style="font-size: 10pt; color: windowtext; text-decoration: none;">Digital Advertising Alliance Opt-Out Tool</span></a><span style="font-size: 10pt;">.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Website Analytics</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We may also partner with selected third-party vendors, such as&nbsp;[List any third-party analytics that your mobile app uses (e.g. Google Analytics)]&nbsp;to allow tracking technologies and remarketing services on the Application through the use of first party cookies and third-party cookies, to, among other things, analyze and track users&rsquo; use of the Application, determine the popularity of certain content, and better understand online activity. By accessing the Application, you consent to the collection and use of your information by these third-party vendors. You are encouraged to review their privacy policy and contact them directly for responses to your questions. We do not transfer personal information to these third-party vendors.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">You should be aware that getting a new computer, installing a new browser, upgrading an existing browser, or erasing or otherwise altering your browser&rsquo;s cookies files may also clear certain opt-out cookies, plug-ins, or settings.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><strong><span style="font-size: 10pt;">Third Party Websites or External Links</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">The Application may contain links to third-party websites and applications of interest, including advertisements and external services, that are not affiliated with us. Once you have used these links to leave the Application, any information you provide to these third parties is not covered by this Privacy Policy, and we cannot guarantee the safety and privacy of your information. Before visiting and providing any information to any third-party websites, you should inform yourself of the privacy policies and practices (if any) of the third party responsible for that website, and should take those steps necessary to, in your discretion, protect the privacy of your information. We are not responsible for the content or privacy and security practices and policies of any third parties, including other sites, services or applications that may be linked to or from the Application.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><strong><span style="font-size: 10pt;">Security of Information</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable, and no method of data transmission can be guaranteed against any interception or other type of misuse. Any information disclosed online is vulnerable to interception and misuse by unauthorized parties. Therefore, we cannot guarantee complete security if you provide personal information.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><strong><span style="font-size: 10pt;">Policy for Children</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">We do not knowingly solicit information from or market to children under the age of 13. If you become aware of any data we have collected from children under age 13, please contact us using the contact information provided below.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><strong><span style="font-size: 10pt;">Controls for Don Not Track Feature</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">Most web browsers and some mobile operating systems&nbsp;[and our mobile applications]&nbsp;include a Do-Not-Track (&ldquo;DNT&rdquo;) feature or setting you can activate to signal your privacy preference not to have data about your online browsing activities monitored and collected. No uniform technology standard for recognizing and implementing DNT signals has been finalized. As such, we do not currently respond to DNT browser signals or any other mechanism that automatically communicates your choice not to be tracked online. If a standard for online tracking is adopted that we must follow in the future, we will inform you about that practice in a revised version of this Privacy Policy.</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><strong><span style="font-size: 10pt;">Acceptance of this Privacy Policy</span></strong></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; orphans: auto; text-indent: 0px; text-transform: none; white-space: normal; widows: auto; word-spacing: 0px; -webkit-text-size-adjust: auto; -webkit-text-stroke-width: 0px; text-decoration: none; text-align: justify;"><span style="font-size: 10pt;">You acknowledge that you have read this Privacy Policy and agree to all its terms and conditions. By using the app or its services you agree to be bound by this Agreement. If you do not agree to abide by the terms of this Policy, you are not authorized to use or access the app and its services.&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; orphans: auto; text-indent: 0px; text-transform: none; white-space: normal; widows: auto; word-spacing: 0px; -webkit-text-size-adjust: auto; -webkit-text-stroke-width: 0px; text-decoration: none; text-align: justify;"><span style="font-size: 10pt;">&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 0.0001pt; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; orphans: auto; text-indent: 0px; text-transform: none; white-space: normal; widows: auto; word-spacing: 0px; -webkit-text-size-adjust: auto; -webkit-text-stroke-width: 0px; text-decoration: none; text-align: justify;"><span style="font-size: 10pt;">&nbsp;</span></p>
                    <p class="MsoNormal" style="margin: 0in 0in 12.75pt; font-size: medium; font-family: Calibri, sans-serif; caret-color: #000000; color: #000000; font-style: normal; font-variant-caps: normal; font-weight: normal; letter-spacing: normal; text-align: justify; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration: none;"><span style="font-size: 10pt;">This document was last updated on June 26, 2020&nbsp;</span></p>',
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => Null,
            ],
            [
                'id' => 25,
                'name' => 'Terms and Conditions',
                'ja_name' => '利用規約',
                'pt_name' => 'Termos e Condições',
                'vi_name' => 'Điều khoản và điều kiện',
                'he_name' => 'תנאים והגבלות',
                'de_name' => 'Geschäftsbedingungen',
                'es_name' => 'Términos y condiciones',
                'fr_name' => 'Termes et conditions',
                'ko_name' => '이용약관',
                'zh_name' => '条款和条件',
                'fil_name' => 'Mga Tuntunin at Kundisyon',
                'ar_name' => 'Ar Terms and Conditions' ,
                'type' => 5,
                'description' => '<p class="MsoNormal" style="margin: 0in 0in 0.25in; font-size: medium; font-family: Calibri, sans-serif; text-align: justify;"><strong><span style="font-size: 10pt;">Terms and Conditions</span></strong></p>' ,
                'ja_description' => null,
                'pt_description' => null,
                'vi_description' => null,
                'he_description' => null,
                'de_description' => null,
                'es_description' => null,
                'fr_description' => null,
                'ko_description' => null,
                'zh_description' => null,
                'fil_description' => null,
                'ar_description' => '<p><strong style="font-family: Calibri, sans-serif; text-align: justify;"><span style="font-size: 10pt;">Terms and Conditions</span></strong></p>',
            ]
        ];

        /*
     | upsert
     |--------------------------------------------------------------------------
     | We are using upsert here as it functions to either insert or update records efficiently.
     | If a record already exists, it updates it; if not, it inserts a new record.
     | This operation compares records using a unique key and supports handling multiple records in a single operation.
     */
        DB::table('page_settings')->upsert(
            $page_settings_record,
            ['id'], // Unique column to determine if a row exists
            ['name','ja_name','pt_name','vi_name','he_name','de_name','es_name','fr_name','ko_name','zh_name','fil_name','ar_name','description','ja_description','pt_description','vi_description','he_description','de_description','es_description','fr_description','ko_description','zh_description','fil_description','ar_description']
        );
    }
}
