FROM openjdk:8-jdk-stretch
RUN mkdir "/home/nezha"
ADD http://dl.mycat.org.cn/1.6.7.6/20201126013625/Mycat-server-1.6.7.6-release-20201126013625-linux.tar.gz /home/nezha
RUN cd /home/nezha && tar -zxvf Mycat-server-1.6.7.6-release-20201126013625-linux.tar.gz && ls -lna

ENV MYCAT_HOME=/home/nezha/mycat
WORKDIR /home/nezha/mycat

ENV TZ Asia/Shanghai

EXPOSE 8066 9066

CMD ["/home/nezha/mycat/bin/mycat", "console","&"]