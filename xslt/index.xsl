<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="/quiz">
        <html>
            <head>
                <meta charset="UTF-8"/>
                <meta name="viewport" content="width=device-width, initial-scale=1"/>
                <title>Geography Quiz</title>
            </head>
            <body>
                <h1>Geography Quiz</h1>
                <p>Try this <xsl:value-of select="count(question)"/>-question quiz about geography!</p>
                <xsl:apply-templates select="question"/>
            </body>
        </html>
    </xsl:template>
    
    <xsl:template match="question">
        <xsl:variable name="index" select="position()"/>
        <xsl:variable name="count" select="last()"/>
        <xsl:variable name="id" select="concat('question-', $index)"/>
        <section class="question" id="{$id}">
            <h2><xsl:apply-templates select="prompt"/></h2>
            <ul class="options">
                <xsl:for-each select="option">
                    <xsl:sort select="." order="ascending"/>
                    <li>
                        <xsl:apply-templates select=".">
                            <xsl:with-param name="id" select="$id"/>
                        </xsl:apply-templates>
                    </li>
                </xsl:for-each>
            </ul>
            <p>Correct answer: <xsl:value-of select="option[@correct]"/></p>
        </section>
    </xsl:template>
    
    <xsl:template match="option">
        <xsl:param name="id"/>
        <label>
            <input type="radio" name="{$id}">
                <xsl:attribute name="value">
                    <xsl:choose>
                        <xsl:when test="@correct">correct</xsl:when>
                        <xsl:otherwise>incorrect</xsl:otherwise>
                    </xsl:choose>
                </xsl:attribute>
            </input>
            <span><xsl:apply-templates/></span>
        </label>
    </xsl:template>
</xsl:stylesheet>
