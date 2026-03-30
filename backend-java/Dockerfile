# Build stage
FROM maven:3.9-eclipse-temurin-17 AS build
WORKDIR /build
COPY pom.xml .
RUN mvn dependency:go-offline -B
COPY src ./src
RUN mvn clean package -DskipTests -B

# Runtime stage
FROM tomcat:10.1-jdk17-temurin
LABEL maintainer="iran-info"

# Remove default Tomcat apps
RUN rm -rf /usr/local/tomcat/webapps/*

# Copy WAR as ROOT app (deployed at /)
COPY --from=build /build/target/iran-info-backend.war /usr/local/tomcat/webapps/ROOT.war

# Create uploads directory
RUN mkdir -p /app/uploads/articles

EXPOSE 8080

CMD ["catalina.sh", "run"]
