<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['template_key'=>'support_ticket_reply','locale'=>'en','subject'=>'Support update for {{reference}}','email_body'=>'There is a new support update for {{reference}}.','sms_body'=>'Support update: {{reference}}','push_title'=>'Support update','push_body'=>'A support ticket was updated.','in_app_body'=>'A support ticket was updated.'],
            ['template_key'=>'support_ticket_update','locale'=>'en','subject'=>'Support ticket {{reference}} updated','email_body'=>'Your support ticket {{reference}} is now {{status}}.','sms_body'=>'Ticket {{reference}} is now {{status}}.','push_title'=>'Ticket updated','push_body'=>'Your support ticket status changed.','in_app_body'=>'Your support ticket is now {{status}}.'],
            ['template_key'=>'security_alert','locale'=>'en','subject'=>'Security alert','email_body'=>'A security event requires your attention.','sms_body'=>'Security alert for your account.','push_title'=>'Security alert','push_body'=>'Review your recent security activity.','in_app_body'=>'Review your recent security activity.'],
            ['template_key'=>'support_ticket_reply','locale'=>'mm','subject'=>'ပံ့ပိုးမှု အပ်ဒိတ် {{reference}}','email_body'=>'သင့်ပံ့ပိုးမှု တောင်းဆိုချက်တွင် အပ်ဒိတ်ရှိပါသည်။','sms_body'=>'ပံ့ပိုးမှု အပ်ဒိတ် {{reference}}','push_title'=>'ပံ့ပိုးမှု အပ်ဒိတ်','push_body'=>'ပံ့ပိုးမှု တောင်းဆိုချက်ကို အပ်ဒိတ်လုပ်ထားပါသည်။','in_app_body'=>'ပံ့ပိုးမှု တောင်းဆိုချက်ကို အပ်ဒိတ်လုပ်ထားပါသည်။'],
        ];
        foreach ($templates as $template) DB::table('notification_templates')->updateOrInsert(['template_key'=>$template['template_key'],'locale'=>$template['locale'],'version'=>1], $template + ['id'=>DB::table('notification_templates')->where(['template_key'=>$template['template_key'],'locale'=>$template['locale'],'version'=>1])->value('id') ?: (string) Str::uuid(),'mandatory'=>false,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        foreach (['low'=>[240,2880],'normal'=>[120,1440],'high'=>[60,720],'urgent'=>[15,240]] as $priority => [$response, $resolution]) DB::table('support_sla_policies')->updateOrInsert(['policy_key'=>'sandbox_default','version'=>1,'priority'=>$priority], ['id'=>DB::table('support_sla_policies')->where(['policy_key'=>'sandbox_default','version'=>1,'priority'=>$priority])->value('id') ?: (string) Str::uuid(),'response_minutes'=>$response,'resolution_minutes'=>$resolution,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
    }
}
