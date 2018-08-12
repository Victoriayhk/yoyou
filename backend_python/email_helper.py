#coding:utf-8

import os
import sys
import time
import smtplib  # 加载smtplib模块
from email.mime.text import MIMEText
from email.utils import formataddr
from email.mime.text import MIMEText
from email.mime.image import MIMEImage
from email.mime.multipart import MIMEMultipart

import logging


def send_email_easy(sender, receiver, mail_subject, mail_content):
    """发送邮件简单版, 不能发图片
    1. 示例
        sender = {
            "server": "smtp.qq.com",
            "server_port": 465,
            "username": "452570607@qq.com",
            "address": "452570607@qq.com",
            "password": "xxxxxx"
        }
        receiver = {
            "address": "julesyi@163.com"
        }
        mail_subject = "test"
        mail_content = "hello, content"
        send_email(sender, receiver, mail_subject, mail_content)
    """
    server = smtplib.SMTP_SSL(
            sender["server"], sender["server_port"])

    try:
        server.login(sender["username"], sender["password"])
        # logging.debug("login succeed.")
    except:
        logging.error("email server login error.")
        return False

    try:
        msg = MIMEText(mail_content,'html','utf-8') 
        msg['Subject'] = mail_subject
        # server.sendmail(sender["address"], receiver["address"], msg.as_string()) 
        # logging.debug("sending email succeed.")
    except:
        logging.error("email server sending message error. mail_subject={}".format(
                mail_subject))
        return False
    
    server.close()
    return True


class EmailHandler(object):
    """发送邮件句柄

    来源: https://blog.csdn.net/max229max/article/details/70923172
    """
    def __init__(self, smtpserver, user, pwd):
        self.smtp = smtplib.SMTP()
        self.smtpserver = smtpserver
        self.smtpuser = user
        self.smtppwd = pwd

    def generateAlternativeEmailMsgRoot(self, strFrom, listTo, listCc, strSubject, strMsgText, strMsgHtml, listImagePath):
        """导入邮件信息
        """
        # 邮件头部信息
        msgRoot = MIMEMultipart('related')
        msgRoot['Subject'] = strSubject
        msgRoot['From'] = strFrom
        msgRoot['To'] = ",".join(listTo)
        if listCc:
            msgRoot['Cc'] = ",".join(listCc)
        # msgRoot.preamble = 'This is a multi-part message in MIME format.'

        # 激活html格式邮件正文
        msgAlternative = MIMEMultipart('alternative')
        msgRoot.attach(msgAlternative)

        msgContent = strMsgText.replace("\n","<br>") if strMsgText else ""
        msgContent += "<br>" + strMsgHtml if strMsgHtml else "" 

        # 引入图片
        if listImagePath and len(listImagePath)>0:
            msgHtmlImg = msgContent + "<br>"
            for imgcount in range(0, len(listImagePath)):
                msgHtmlImg += '<img src="cid:image{count}"><br>'.format(count=imgcount)
            msgText = MIMEText(msgHtmlImg, 'html')
            msgAlternative.attach(msgText)

            # This example assumes the image is in the current directory
            for i,imgpath in enumerate(listImagePath):
                assert(os.path.exists(imgpath))
                fp = open(imgpath, 'rb')
                msgImage = MIMEImage(fp.read())
                fp.close()

                # Define the image's ID as referenced above
                msgImage.add_header('Content-ID', '<image{count}>'.format(count=i))
                msgRoot.attach(msgImage)
        else:
            msgText = MIMEText(msgContent, 'html')
            msgAlternative.attach(msgText)

        return msgRoot

    def send_email(self, strFrom, listTo, strSubject, strMsgText, strMsgHtml=None, listImagePath=None, listCc=None):
        """发送邮件"""
        msgRoot = self.generateAlternativeEmailMsgRoot(strFrom, listTo, listCc, strSubject, strMsgText, strMsgHtml, listImagePath)

        try:
            self.smtp = smtplib.SMTP()
            self.smtp.connect(self.smtpserver)
            self.smtp.login(self.smtpuser, self.smtppwd)
            if listCc:
                listTo = listTo + listCc
            self.smtp.sendmail(strFrom, listTo, msgRoot.as_string())
            self.smtp.quit()
            logger.info("Send mail success {0}".format(strSubject))
        except Exception as e:
            logging.error("ERROR:Send mail failed {0} with {1}".format(strSubject, str(e)))


def test():
    # send_email_easy() 使用
    # sender = {
    #     "server": "smtp.qq.com",
    #     "server_port": 465,
    #     "username": "452570607@qq.com",
    #     "address": "452570607@qq.com",
    #     "password": ""
    # }
    # receiver = {
    #     "address": "julesyi@163.com"
    # }
    # mail_subject = "test"
    # mail_content = "hello, content"
    # send_email(sender, receiver, mail_subject, mail_content)

    # EmailHandler使用
    smtpserver = "smtp.qq.com"
    smtpport = 465
    username = "4525706070@qq.com"
    password = ""
    strFrom = '4525706070@qq.com'
    strTo = ['julesyi@163.com']
    strCc = []
    strSubject = 'test email - text with image'
    eh = EmailHandler(smtpserver, username, password)
    imgpath = "test.png"
    imgpath2 = "test2.png"
    # eh.send_email(strFrom,strTo,"text mail","Hi it's Max, this is a test maill-----1","<h2>test html content</h2>")
    eh.send_email(strFrom,strTo,"image mail","Hi it's Max,\n this is a test maill-----2","<h2>test html content</h2>", [imgpath,imgpath2], listCc=strCc)
    # eh.send_email(strFrom,strTo,"image mail","Hi it's Max, this is a test maill-----2",listImagePath=[imgpath])

if __name__ == "__main__":
    logging.basicConfig(level=logging.DEBUG,
        format='%(asctime)s %(filename)s[line:%(lineno)d] %(levelname)s %(message)s',
        filemode='w+')
    test()







