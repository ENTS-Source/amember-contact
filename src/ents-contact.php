<?php

/**
 * Class Am_Plugin_EntsContact
 */
class Am_Plugin_EntsContact extends Am_Plugin
{
    const PLUGIN_STATUS = self::STATUS_PRODUCTION;
    const PLUGIN_COMM = self::COMM_FREE;
    const PLUGIN_REVISION = "1.0.0";

    function onUserMenu(Am_Event $event)
    {
        $menu = $event->getMenu();
        $menu->addPage(array(
            'id' => 'ents-contact',
            'controller' => 'ents-contact',
            'action' => 'index',
            'label' => ___('Contact Us'),
            'order' => 1000
        ));
    }

    function onSetupForms(Am_Event_SetupForms $event)
    {
        $form = new Am_Form_Setup("ents-contact");
        $form->setTitle("ENTS: Contact");

        $form->addText("send_to_address")->setLabel(___("Send to address\nempty - send to administrators"));

        $form->addFieldsPrefix("misc.ents-contact.");
        $event->addForm($form);
    }

    function getReadme()
    {
        return <<<CUT
This plugin provides members with a contact link in their member portal. The email address specified above will receive messages when someone submits the available form.

Plugin created by ENTS (Edmonton New Technology Society)
* Source: https://github.com/ENTS-Source/amember-contact
* For help and support please contact us: https://ents.ca/contact/
CUT;
    }
}

/**
 * Class EntsContactController
 */
class EntsContactController extends Am_Mvc_Controller
{
    public function indexAction()
    {
        $this->view->title = "Contact Us";
        $form = new Am_Form();

        $user = $this->getDi()->user;
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array("replyTo" => $user->getEmail())));

        $replyTo = $form->addElement('text', 'replyTo')->setLabel(___("Email Address: "));
        $message = $form->addElement('textarea', 'msg', array("style" => "height: 70px; width: 70%;"))->setLabel(___("Message: "));
        $form->addElement('submit', null, array("value" => ___("Send")));

        $replyTo->addFilter("trim");
        $message->addFilter("trim");
        $replyTo->addRule("required", ___("Please enter an email address we can reply to"));
        $message->addRule("required", ___("Please enter a message"));

        if ($form->isSubmitted() && $form->validate()) {
            $rawMesasge = htmlspecialchars($message->getValue());
            $rawEmailAddress = htmlspecialchars($replyTo->getValue());
            $this->sendMessage($rawEmailAddress, $rawMesasge);

            $this->view->content = ___("Your message has been sent.");
        } else {
            $this->view->content = (string)$form;
        }

        $this->view->display("layout.phtml");
    }

    private function sendMessage($from, $message)
    {
        $mail = $this->getDi()->mail;
        $user = $this->getDi()->user;

        // TODO: #1 - Make this an email template
        $subject = "[AMP] New message from {$user->getName()}";
        $body = "Hello,\n{$user->getName()} ($from) has sent you a message: \n\n$message";

        $sendTo = $this->getDi()->plugins_misc->get("ents-contact")->getConfig("send_to_address", "");
        if (strlen(trim($sendTo)) == 0) {
            $mail->toAdmin();
        } else {
            $mail->addTo($sendTo);
        }

        $mail->setReplyTo($from);
        $mail->setSubject($subject);
        $mail->setBodyText($body);

        $mail->send(); // this can throw, but we'll leave the error unhandled
    }
}